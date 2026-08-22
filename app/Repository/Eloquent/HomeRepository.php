<?php

namespace App\Repository\Eloquent;


use App\Repository\Interfaces\HomeInterface;
use App\Models\Plan;
use App\Models\Visit;
use App\Models\User;
use App\Models\Product;
use App\Models\Account;
use App\Models\Customer;
use App\Models\SiteLog;
use App\Models\Attendance;
use App\Http\Resources\API\PlansResource;
use App\Http\Resources\API\VisitsResource;
use App\Http\Resources\API\LogsResource;
use App\Enums\PositionKey;
use App\Enums\PlanStatusEnum;
use App\Enums\AttendanceStatusEnum;
use Carbon\Carbon;


class HomeRepository implements HomeInterface
{
      
	  public function getAll()
	  {
		$limit = 10;
		$currentDate = Carbon::today();
		$plans = Plan::select('plans.*')->whereHas('user', function ($query) {$query->where('users.status',1);})
		    ->where('plans.status',1)->whereDate('plans.start_date', '<=', $currentDate)->whereDate('plans.end_date', '>=', $currentDate)->orderBy('plans.created_at','DESC')->paginate($limit);
	  
		$visits = Visit::select('visits.*')->join('plans','plans.id','=','visits.plan_id')->whereHas('user', function ($query) {$query->where('users.status',1);})
		   ->whereDate('visits.actual_start_date', '=', $currentDate)->where('visits.status',2)->with('user:id,name', 'account:id,name', 'customer:id,name,image')->orderBy('visits.created_at','DESC')->paginate($limit);

		$logs = SiteLog::orderBy('site_logs.created_at','DESC')->paginate($limit);   

		$statistics = $this->statistics();

	 $data = ["current_plans"=>PlansResource::collection($plans)
				,"current_visits"=> VisitsResource::collection($visits)
				,"logs"=>LogsResource::collection($logs),
				"statistics"=>$statistics,
				// New blocks powering the dashboard concept: trend badges on
				// the KPI cards, an "action center" of things needing
				// attention, an attendance breakdown for today, and a
				// 7-day visits trend to replace the old sales chart.
				"trends"=>$this->trends(),
				"action_center"=>$this->actionCenter(),
				"attendance_summary"=>$this->attendanceSummary(),
				"visits_trend"=>$this->visitsTrend(),
			 ];
		return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];
	  }

	  protected function statistics()
	{
		$currentDate = Carbon::today();

		return [
			'total_admin' => User::where('is_admin', 1)->where('status', 1)->count(),

			'total_managers' => User::where('is_admin', '!=', 1)
				->whereHas('userposition', fn ($q) =>
					$q->where('ps_key','!=', PositionKey::SALES_REP->value)
				)->where('status', 1)->count(),

			'total_salesrep' => User::where('is_admin', '!=', 1)
				->whereHas('userposition', fn ($q) =>
					$q->where('ps_key', PositionKey::SALES_REP->value)
				)->where('status', 1)->count(),

			'total_products' => Product::count(),

			'total_accounts' => Account::count(),

			'total_customers' => Customer::count(),

			'total_current_visits' => Visit::has('plan')->whereDate('actual_start_date', $currentDate)
				->where('status', 2)->count(),

			'total_current_plans' => Plan::has('user')->whereDate('start_date', '<=', $currentDate)
				->where('end_date', '>=', $currentDate)->where('status', 1)->count(),
		];
	}

	/**
	 * Day-over-day percentage change for the KPIs that make sense to
	 * trend (visits completed, attendance rate). Everything else on
	 * the dashboard (admin/manager/rep counts) barely changes day to
	 * day, so a trend arrow on those would be noise, not signal.
	 */
	protected function trends(): array
	{
		$today = Carbon::today();
		$yesterday = Carbon::yesterday();

		$visitsToday = Visit::whereDate('actual_start_date', $today)
			->where('status', 2)
			->count();

		$visitsYesterday = Visit::whereDate('actual_start_date', $yesterday)
			->where('status', 2)
			->count();

		$attendanceToday = $this->attendanceRateFor($today);
		$attendanceYesterday = $this->attendanceRateFor($yesterday);

		return [
			'visits_today' => $visitsToday,
			'visits_change_pct' => $this->percentChange($visitsYesterday, $visitsToday),

			'attendance_rate_today' => $attendanceToday,
			'attendance_change_pct' => $this->percentChange($attendanceYesterday, $attendanceToday),
		];
	}

	protected function percentChange($previous, $current): float
	{
		if ($previous == 0) {
			return $current > 0 ? 100.0 : 0.0;
		}

		return round((($current - $previous) / $previous) * 100, 1);
	}

	/**
	 * "Needs your attention" block: things an admin would otherwise have
	 * to go dig for across three different screens.
	 */
	protected function actionCenter(): array
	{
		$today = Carbon::today();

		// Visits that never got marked as Visited and whose date has
		// already passed — same "missed" definition used elsewhere
		// (status != Visited AND visit_date is in the past).
		$missedVisits = Visit::where('status', '!=', 2)
			->whereDate('visit_date', '<', $today)
			->count();

		$pendingPlanApprovals = Plan::where('status', PlanStatusEnum::Pending)
			->whereDate('end_date', '>=', $today)
			->count();

		$supervisorsWithoutReps = $this->countSupervisorsWithoutReps();

		return [
			'missed_visits' => $missedVisits,
			'pending_plan_approvals' => $pendingPlanApprovals,
			'supervisors_without_reps' => $supervisorsWithoutReps,
		];
	}

	/**
	 * ASSUMPTION: supervisor -> reps is modeled via `users.manager_id`
	 * (a rep's manager_id points at their supervisor), matching the
	 * pattern used elsewhere in this codebase (SupervisorRepository).
	 * Deliberately avoids relying on a `getAllSubordinateIds()` call per
	 * supervisor (N+1) — this does it in two queries total.
	 */
	protected function countSupervisorsWithoutReps(): int
	{
		$supervisorIds = User::where('status', 1)
			->whereHas('userposition', fn ($q) =>
				$q->where('ps_key', PositionKey::SUPERVISOR->value)
			)
			->pluck('id');

		if ($supervisorIds->isEmpty()) {
			return 0;
		}

		$supervisorIdsWithReps = User::whereIn('manager_id', $supervisorIds)
			->whereHas('userposition', fn ($q) =>
				$q->where('ps_key', PositionKey::SALES_REP->value)
			)
			->distinct()
			->pluck('manager_id');

		return $supervisorIds->diff($supervisorIdsWithReps)->count();
	}

	/**
	 * Today's attendance breakdown (present / late / absent), for the
	 * small stacked-bar widget on the dashboard. Mirrors the logic in
	 * AttendanceReportRepository::dailySummary() but condensed to just
	 * the three counts the widget needs, for a single date.
	 */
	protected function attendanceSummary(): array
	{
		return $this->attendanceCountsFor(Carbon::today());
	}

	protected function attendanceCountsFor(Carbon $date): array
	{
		$employeeIds = User::where('is_admin', 0)->where('status', 1)->pluck('id');
		$total = $employeeIds->count();

		$attendances = Attendance::whereDate('attendance_date', $date)
			->whereIn('user_id', $employeeIds)
			->get();

		$present = $attendances->where('status', AttendanceStatusEnum::PRESENT)->count();
		$late = $attendances->whereIn('status', [
			AttendanceStatusEnum::LATE_ARRIVAL,
			AttendanceStatusEnum::LATE_ARRIVAL_LEAVE_EARLY,
		])->count();

		// Anyone expected to work today with no attendance row at all
		// counts as absent, same convention as the attendance report.
		$absent = max($total - $attendances->count(), 0);

		return [
			'present' => $present,
			'late' => $late,
			'absent' => $absent,
			'total' => $total,
		];
	}

	protected function attendanceRateFor(Carbon $date): float
	{
		$counts = $this->attendanceCountsFor($date);

		if ($counts['total'] === 0) {
			return 0.0;
		}

		return round(($counts['present'] / $counts['total']) * 100, 1);
	}

	/**
	 * Last 7 days of completed visits, replacing the old sales chart
	 * (this business doesn't track sales — visits are the operational
	 * metric that matters).
	 */
	protected function visitsTrend(): array
	{
		return collect(range(6, 0))
			->map(function (int $daysAgo) {
				$date = Carbon::today()->subDays($daysAgo);

				return [
					'date' => $date->toDateString(),
					'label' => $date->format('D'),
					'count' => Visit::whereDate('actual_start_date', $date)
						->where('status', 2)
						->count(),
				];
			})
			->values()
			->toArray();
	}

	  public function getAllLogs(){

		$limit = (is_numeric(request()->get('per_page'))) && (request()->get('per_page') > 0) ? request()->get('per_page') : 20;
		$logs = SiteLog::orderBy('site_logs.created_at','DESC')->paginate($limit); 
		   $data = LogsResource::collection($logs);
		return ["status"=>true, "message"=>trans('messages.success'),'data'=>$data];
	  }


	  

}
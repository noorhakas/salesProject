<?php

namespace App\Repository\Eloquent;

use App\Repository\Interfaces\PlanInterface;
use App\Http\Resources\API\PlansResource;
use App\Http\Resources\API\PlanDetailResource;

use App\Models\Plan;
use App\Models\PlanStatus;
use App\Models\User;
use App\Models\Visit;
use App\Enums\VisitStatusEnum;
use App\Enums\PlanStatusEnum;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Traits\PaginatesResults;

class PlanRepository implements PlanInterface
{
    use PaginatesResults;

    protected const DEFAULT_PER_PAGE = 20;

    protected NotificationService $notifications;

    public function __construct(NotificationService $notifications)
    {
        $this->notifications = $notifications;
    }

    public function getMyPlans($request)
    {
       // $this->applyDefaultDateRange($request);

        $recentPlan = User::getCurrentPlan();
        $recentPlanResource = $recentPlan ? new PlansResource($recentPlan) : (object) [];

        $previousPlansQuery = auth()->user()->plans()
            ->filter($request)->orderBy('plans.created_at', 'DESC');

        if ($recentPlan) {
            $previousPlansQuery->where('id', '!=', $recentPlan->id);
        }

        $previousPlans = $this->paginateOrAll($previousPlansQuery, $request);

        $data = [
            'recent_plans'   => $recentPlanResource,
            'previous_plans' => PlansResource::collection($previousPlans),
        ];

        return $this->success($data);
    }

    public function getALL($request)
    {

        $query = Plan::select('plans.*')
            ->join('users', 'users.id', '=', 'plans.user_id')
            ->filter($request)
            ->orderBy('plans.created_at', 'DESC');

        $plans = $this->paginateOrAll($query, $request);    

        return $this->success(PlansResource::collection($plans));
    }

    public function createNewPlan($request)
    {
        $user = auth()->user();
        $userId = auth()->user()->id ?? 0;
        $visitList = collect($request->visit_list);

        $startDate = Carbon::parse($visitList->min('visit_date'))->toDateString();
        $endDate   = Carbon::parse($visitList->max('visit_date'))->toDateString();


        if ($this->hasOverlappingPlan($user, $startDate, $endDate)) {

            return $this->failure(
                'You already have a plan in this date range'
            );
        }

        try {
           DB::beginTransaction();

            $plan = $this->createPlan([
                'min_date' => $startDate,
                'max_date' => $endDate,
                'type'     => 0,
            ]);

            foreach ($visitList as $visit) {
                $this->upsertVisit($plan, $visit, $userId);
            }

           DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Plan creation failed: ' . $e->getMessage(), [
                'user_id'   => $userId,
                'exception' => $e,
            ]);

            return $this->failure('server_error');
        }
        $this->notifications->sendNewPlanCreated($plan, auth()->user());

         return $this->success(new PlansResource($plan));
    }

   
    private function hasOverlappingPlan($user, $startDate, $endDate): bool
    {
        return $user->plans()
            ->where('status', '!=', PlanStatusEnum::Rejected)
            ->where(function ($q) use ($startDate, $endDate) {

                $q->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate])
                ->orWhere(function ($q) use ($startDate, $endDate) {

                    $q->where('start_date', '<=', $startDate)
                        ->where('end_date', '>=', $endDate);

                });

            })
            ->exists();
    }

    public function show($plan_id)
    {
        try {
            $plan = Plan::find($plan_id);

            if (!$plan) {
                return $this->failure('data_not_found');
            }

            $data = [
                'plan'        => new PlansResource($plan),
                'listOfDates' => $this->buildDateRange($plan->start_date, $plan->end_date),
            ];

            return $this->success($data);
        } catch (\Exception $e) {
            Log::error('Plan show failed: ' . $e->getMessage(), ['plan_id' => $plan_id, 'exception' => $e]);

            return $this->failure('server_error');
        }
    }

    public function deletePlan($plan)
    {
        try {
            if (!$plan) {
                return $this->failure('data_not_found');
            }

            $plan->delete();

            return ['status' => true, 'message' => trans('messages.success')];
        } catch (\Exception $e) {
            Log::error('Plan deletion failed: ' . $e->getMessage(), ['exception' => $e]);

            return $this->failure('server_error');
        }
    }

    /**
     * Accept a plan. 
     */
    public function acceptPlan($request)
    {
        return $this->reviewPlan($request, PlanStatusEnum::Accepted);
    }

    /**
     * Reject a plan. 
     */
    public function rejectPlan($request)
    {
        return $this->reviewPlan($request, PlanStatusEnum::Rejected);
    }


   protected function reviewPlan($request, int $status): array
    {
        $planId = $request->plan_id;
        $reviewer = auth()->user();
        $approvedBy = $reviewer->id ?? 0;
        $note = $request->note ?? null;

        try {
            DB::beginTransaction();

            $plan = Plan::findOrFail($planId);
            $owner = User::findOrFail($plan->user_id);

            $plan->update([
                'status'                  => $status,
                'approved_or_rejected_by' => $approvedBy,
            ]);

            PlanStatus::updateOrCreate(
                ['plan_id' => $planId, 'approved_or_rejected_by' => $approvedBy],
                [
                    'status'                  => $status,
                    'approved_or_rejected_by' => $approvedBy,
                    'note'                    => $note,
                ]
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Plan review failed: ' . $e->getMessage() . ' at line ' . $e->getLine() . ' in ' . $e->getFile(), [
                'plan_id'   => $planId,
                'exception' => $e,
            ]);

            return $this->failure('server_error');
        }

        $this->notifications->sendPlanReviewed($plan, $owner, $status, $reviewer, $note);

        if ($status === PlanStatusEnum::Accepted) {
            $this->notifications->sendVisitRequests($plan, $owner);
        }

        return ['status' => true, 'message' => trans('messages.success')];
    }


    public function statistics($request, array $subordinateIds): array
    {
        //$this->applyDefaultDateRange($request);

        $today = Carbon::now()->toDateString();

        $stats = Plan::join('users', 'users.id', '=', 'plans.user_id')
                ->whereIn('plans.user_id', $subordinateIds)
                ->filter($request, false)
            ->selectRaw("
                COUNT(plans.id) as total,
                SUM(CASE WHEN plans.status = ? AND plans.end_date >= ? THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN plans.status = ? AND plans.end_date <  ? THEN 1 ELSE 0 END) as expired,
                SUM(CASE WHEN plans.status = ? AND plans.end_date >= ? THEN 1 ELSE 0 END) as accepted,
                SUM(CASE WHEN plans.status = ? THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN plans.status = ? AND plans.end_date <  ? THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN plans.status = ? AND plans.start_date > ? THEN 1 ELSE 0 END) as upcoming,
                SUM(CASE WHEN plans.status = ? AND plans.start_date <= ? AND plans.end_date >= ? THEN 1 ELSE 0 END) as in_progress
            ", [
                PlanStatusEnum::Pending, $today,           // pending (still within window)
                PlanStatusEnum::Pending, $today,           // expired (window passed, no decision)
                PlanStatusEnum::Accepted, $today,          // accepted (accepted, NOT finished yet)
                PlanStatusEnum::Rejected,
                PlanStatusEnum::Accepted, $today,          // completed (accepted AND finished)
                PlanStatusEnum::Accepted, $today,          // upcoming (breakdown within accepted)
                PlanStatusEnum::Accepted, $today, $today,  // in_progress (breakdown within accepted)
            ])
            ->first();

        return [
            // pending + expired + accepted + rejected + completed = total,
            // each plan counted in exactly one of these five buckets.
            'total'     => (int) $stats->total,
            'pending'   => (int) $stats->pending,
            'expired'   => (int) $stats->expired,
            'accepted'  => (int) $stats->accepted,
            'rejected'  => (int) $stats->rejected,
            'completed' => (int) $stats->completed,
                'upcoming'    => (int) $stats->upcoming,
                'in_progress' => (int) $stats->in_progress,
            
        ];
    }


    public function getManagerPlans($request, array $subordinateIds)
    {
        $query = Plan::select('plans.*')->whereHas('user')
                ->whereIn('plans.user_id', $subordinateIds)
                ->filter($request)
                ->orderBy('plans.created_at', 'DESC');

        $plans = $this->paginateOrAll($query, $request);        

        return $this->success(PlansResource::collection($plans));
    }

    public function showForManager($plan_id, array $subordinateIds)
    {
        try {
             $plan = Plan::with(['user.branches:id,name','user.branchDepartments.branch:id,name','user.branchDepartments.department:id,name','user.manager:id,name',
                                'user.userposition'])->whereIn('user_id', $subordinateIds)->find($plan_id);

            if (!$plan) {
                return $this->failure('data_not_found');
            }

            $data = [
                'plan'        => new PlanDetailResource($plan),
                'listOfDates' => $this->buildDateRange($plan->start_date, $plan->end_date),
            ];

            return $this->success($data);
        } catch (\Exception $e) {
            Log::error('Plan show (manager) failed: ' . $e->getMessage(), ['plan_id' => $plan_id, 'exception' => $e]);

            return $this->failure('server_error');
        }
    }

    /**
     * Builds the day-by-day calendar strip used by the plan detail screen.
     */
    protected function buildDateRange(string $startDate, string $endDate): array
    {
        $days = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate));
        $dates = [];

        for ($i = 0; $i <= $days; $i++) {
            $day = Carbon::parse($startDate)->addDays($i);

            $dates[] = [
                'date'   => $day->toDateString(),
                'number' => $day->day,
                'day'    => substr($day->dayName, 0, 3),
            ];
        }

        return $dates;
    }

    protected function createPlan(array $data): Plan
    {
        $userId = auth()->user()->id ?? 0;

        return Plan::updateOrCreate(
            ['user_id' => $userId, 'start_date' => $data['min_date'], 'status' => PlanStatusEnum::Pending],
            [
                'user_id'    => $userId,
                'start_date' => $data['min_date'],
                'end_date'   => $data['max_date'],
                'type'       => $data['type'],
            ]
        );
    }

    /**
     * Creates/updates a single Visit row from one entry of the incoming
     * visit_list payload.
     */
    protected function upsertVisit(Plan $plan, array $visit, int $userId): void
    {
        $doctorId = $visit['doctor_id'] ?? 0;
        $combineWith = $visit['combine_with'] ?? 0;

        $attributes = [
            'account_id'  => $visit['account_id'],
            'customer_id' => $doctorId,
            'user_id'     => $userId,
            'visit_date'  => $visit['visit_date'],
        ];

        $values = array_merge($attributes, [
            'plan_id'      => $plan->id,
            'combine_with' => $combineWith,
            'status'       => (VisitStatusEnum::Pending)['id'],
            'start_time'   => Carbon::parse($visit['start_time'])->format('H:i:s'),
            'end_time'     => Carbon::parse($visit['end_time'])->format('H:i:s'),
        ]);

        Visit::updateOrCreate($attributes, $values);
    }

    
    protected function applyDefaultDateRange($request): void
    {
        if (!$request->filled('start_date') && !$request->filled('end_date')) {
            $request->merge([
                'start_date' => Carbon::now()->startOfMonth()->toDateString(),
                'end_date'   => Carbon::now()->endOfMonth()->toDateString(),
            ]);
        }
    }

   
}
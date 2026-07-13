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

class PlanRepository implements PlanInterface
{
    // Plan.status values. Move these to a proper PlanStatusEnum if/when
    // other parts of the app need to share them (mirrors VisitStatusEnum).
    protected const STATUS_PENDING  = 0;
    protected const STATUS_ACCEPTED = 1;
    protected const STATUS_REJECTED = 2;

    // User.position values.
    protected const POSITION_REP = 3;

    protected const DEFAULT_PER_PAGE = 20;

    protected NotificationService $notifications;

    public function __construct(NotificationService $notifications)
    {
        $this->notifications = $notifications;
    }

    public function getMyPlans($request)
    {
        $limit = $this->resolvePerPage($request);

        $recentPlan = User::getCurrentPlan();
        $recentPlanResource = $recentPlan ? new PlansResource($recentPlan) : (object) [];

        $previousPlansQuery = auth()->user()->plans()
            ->filter($request)->orderBy('plans.created_at', 'DESC');

        // Exclude the "recent" plan from the previous-plans list so it
        if ($recentPlan) {
            $previousPlansQuery->where('id', '!=', $recentPlan->id);
        }

        $previousPlans = $previousPlansQuery->paginate($limit);

        $data = [
            'recent_plans'   => $recentPlanResource,
            'previous_plans' => PlansResource::collection($previousPlans),
        ];

        return $this->success($data);
    }

    public function getALL($request)
    {
        $limit = $this->resolvePerPage($request);

        $plans = Plan::select('plans.*')
            ->join('users', 'users.id', '=', 'plans.user_id')
            ->filter($request)
            ->orderBy('plans.created_at', 'DESC')
            ->paginate($limit);

        return $this->success(PlansResource::collection($plans));
    }

    public function createNewPlan($request)
    {
        $userId = auth()->user()->id ?? 0;
        $visitList = collect($request->visit_list);

        try {
            DB::beginTransaction();

            $plan = $this->createPlan([
                'min_date' => $visitList->min('visit_date'),
                'max_date' => $visitList->max('visit_date'),
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

        return ['status' => true, 'message' => trans('messages.plan_reviewed')];
    }

    public function show($plan_id)
    {
        try {
            $plan = Plan::find($plan_id);

            if (!$plan) {
                return $this->failure('data_not_found');
            }

            $isRep = auth()->user()->position == self::POSITION_REP;

            if ($plan->status == self::STATUS_PENDING && $isRep) {
                return $this->failure('plan_reviewed');
            }

            if ($plan->status == self::STATUS_REJECTED && $isRep) {
                $note = optional($plan->plan_status()->where('status', self::STATUS_REJECTED)->first())->note;

                return ['status' => false, 'message' => trans('messages.plan_rejected') . '. ' . $note];
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

    public function AcceptOrRejectPlan($request)
    {
        $planId = $request->plan_id;
        $status = $request->status; // 1 = Accepted, 2 = Rejected
        $reviewer = auth()->user();
        $approvedBy = $reviewer->id ?? 0;

        try {
            DB::beginTransaction();

            $plan = Plan::findOrFail($planId);
            $owner = User::findOrFail($plan->user_id);

            $plan->update([
                'status'                   => $status,
                'approved_or_rejected_by'  => $approvedBy,
            ]);

            PlanStatus::updateOrCreate(
                ['plan_id' => $planId, 'approved_or_rejected_by' => $approvedBy],
                array_merge($request->validated(), [
                    'status'                  => $status,
                    'approved_or_rejected_by' => $approvedBy,
                ])
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Plan approval failed: ' . $e->getMessage() . ' at line ' . $e->getLine() . ' in ' . $e->getFile(), [
                'plan_id'   => $planId,
                'exception' => $e,
            ]);

            return $this->failure('server_error');
        }

        // As above: notifications are fired after the transaction commits
        // so a notification failure can never undo a real status change.
        $this->notifications->sendPlanReviewed($plan, $owner, (int) $status, $reviewer);

        if ((int) $status === self::STATUS_ACCEPTED) {
            $this->notifications->sendVisitRequests($plan, $owner);
        }

        return ['status' => true, 'message' => trans('messages.success')];
    }


     public function statistics($request, array $subordinateIds): array
    {
        $stats = Plan::join('users', 'users.id', '=', 'plans.user_id')
                ->whereIn('plans.user_id', $subordinateIds)
            ->selectRaw("
                COUNT(plans.id) as total,
                SUM(CASE WHEN plans.status = ? THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN plans.status = ? THEN 1 ELSE 0 END) as accepted,
                SUM(CASE WHEN plans.status = ? THEN 1 ELSE 0 END) as rejected
            ", [
                PlanStatusEnum::Pending,
                PlanStatusEnum::Accepted,
                PlanStatusEnum::Rejected
            ])
            ->first();

        $total = (int) $stats->total;
        $pending = (int) $stats->pending;
        $accepted = (int) $stats->accepted;
        $rejected = (int) $stats->rejected;

        return [
            'total'   => $total,
            'pending' => $pending,
            'accepted'    => $accepted,
            'rejected'   => $rejected, 
        ];
    }


    public function getManagerPlans($request, array $subordinateIds)
    {
        $limit = $this->resolvePerPage($request);

        $plans = Plan::select('plans.*')->whereHas('user')
                ->whereIn('plans.user_id', $subordinateIds)
                ->filter($request)
                ->orderBy('plans.created_at', 'DESC')
                ->paginate($limit);

        return $this->success(PlansResource::collection($plans));
    }

    public function showForManager($plan_id, array $subordinateIds)
    {
        try {
            $plan = Plan::whereIn('user_id', $subordinateIds)->find($plan_id);

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
            ['user_id' => $userId, 'start_date' => $data['min_date'], 'status' => self::STATUS_PENDING],
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

    protected function resolvePerPage($request): int
    {
        return (is_numeric($request->per_page) && $request->per_page > 0)
            ? (int) $request->per_page
            : self::DEFAULT_PER_PAGE;
    }

    protected function success($data): array
    {
        return ['status' => true, 'message' => trans('messages.success'), 'data' => $data];
    }

    protected function failure(string $messageKey): array
    {
        return ['status' => false, 'message' => trans("messages.{$messageKey}")];
    }
}
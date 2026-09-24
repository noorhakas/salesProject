<?php

namespace App\Repository\Eloquent;

use App\Repository\Interfaces\VisitInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\Plan;
use App\Models\User;
use App\Models\Visit;
use App\Models\Gift;
use App\Models\Setting;
use App\Models\VisitDetails;

use App\Enums\GiftTypeEnum;
use App\Enums\VisitStatusEnum;

use App\Http\Resources\API\VisitAccessoryResource;
use App\Http\Resources\API\VisitDetailResource;
use App\Http\Resources\API\VisitsResource;
use App\Http\Resources\API\UserResource;
use App\Http\Resources\API\VisitStatisticsResource;

use App\Services\NotificationService;
use App\Http\Traits\PaginatesResults;

class VisitRepository implements VisitInterface
{
    use PaginatesResults;

    protected NotificationService $notifications;

    public function __construct(NotificationService $notifications)
    {
        $this->notifications = $notifications;
    }

    /*
    |--------------------------------------------------------------------------
    | Shared Queries
    |--------------------------------------------------------------------------
    */

    /**
     * Base query used by visit listing endpoints.
     */
    protected function visitsQuery()
    {
        return Visit::query()
            ->select('visits.*')
            ->whereHas('user')
            ->join('accounts', 'accounts.id', '=', 'visits.account_id')
            ->leftJoin('customers', 'customers.id', '=', 'visits.customer_id')
            ->with([
                'user:id,name',
                'account:id,name',
                'customer:id,name,image',
            ]);
    }

    /**
     * Apply optional user filter.
     */
    protected function filterByUser($query, $request)
    {
        return $query->when(
            $request->filled('user_id'),
            fn ($q) => $q->where('visits.user_id', $request->user_id)
        );
    }

    /**
     * Main listing query.
     */
    protected function buildVisitsQuery($request)
    {
        return $this->filterByUser(
            $this->visitsQuery(),
            $request
        )
        ->filter($request)
        ->orderBy('visits.visit_date','desc')
        ->orderBy('visits.start_time');
    }

    /**
     * Return paginated visits.
     */
    protected function getPaginatedVisits(
        $request,
        $perPage = self::ALL_RESULTS
    ) {
        $query = $this->buildVisitsQuery($request);

        $visits = $this->paginateOrAll(
            $query,
            $request,
            $perPage
        );

        return VisitsResource::collection($visits);
    }


    /*
    |--------------------------------------------------------------------------
    | Basic Visits
    |--------------------------------------------------------------------------
    */

    public function getUservisits($request)
    {
        if ($request->filled('plan_id')) {

            $plan = Plan::find($request->plan_id);

            if (!$plan) {
                return $this->success([]);
            }

            $visits = $plan->visits();

        } else {

            $visits = auth()->user()->visits();
        }

        $query = $this->joinAccountsAndCustomers($visits)
            ->select('visits.*')
            ->with([
                'user:id,name',
                'account:id,name',
                'customer' => function ($q) {
                    $q->select(
                        'id',
                        'name',
                        'image',
                        'account_id',
                        'class_id'
                    )->with([
                        'account:id,name,lat,lng',
                        'class:id,name',
                    ]);
                },
            ])
            ->filter($request)
            ->orderBy('visits.visit_date','desc')
            ->orderBy('visits.start_time');

        $visits = $this->paginateOrAll(
            $query,
            $request
        );

        return $this->success(
            VisitsResource::collection($visits)
        );
    }


    public function getvisitsByPlan($request)
    {
        if (!$request->filled('plan_id')) {
            return $this->success([]);
        }

        $plan = Plan::find($request->plan_id);

        if (!$plan) {
            return $this->success([]);
        }

        $query = $this->joinAccountsAndCustomers(
            $plan->visits()
        )
        ->select('visits.*')
        ->with([
            'user:id,name',
            'account:id,name',
            'customer:id,name,image',
        ])
        ->filter($request)
        ->orderBy('visits.visit_date','desc')
        ->orderBy('visits.start_time');

        $visits = $this->paginateOrAll(
            $query,
            $request,
            self::ALL_RESULTS
        );

        return $this->success(
            VisitsResource::collection($visits)
        );
    }


    public function getvisitDtail($id)
    {
        $visit = Visit::find($id);

        if (!$visit) {
            return $this->failure('data_not_found');
        }

        return $this->success(
            $this->buildVisitDetailData($visit)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Manager Visits
    |--------------------------------------------------------------------------
    */

    public function getVisitsForManager(
        $request,
        array $subordinateIds
    ) {
        $query = $this->visitsQuery()
            ->whereIn('visits.user_id', $subordinateIds);

        $query = $this->filterByUser($query, $request)
            ->filter($request)
            ->orderByDesc('visits.created_at');

        $visits = $this->paginateOrAll(
            $query,
            $request,
            self::ALL_RESULTS
        );

        return $this->success(
            VisitsResource::collection($visits)
        );
    }


    public function showVisitForManager(
        $id,
        array $subordinateIds
    ) {
        $visit = Visit::whereIn(
            'user_id',
            $subordinateIds
        )->find($id);

        if (!$visit) {
            return $this->failure('data_not_found');
        }

        return $this->success(
            $this->buildVisitDetailData($visit)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Current Visits
    |--------------------------------------------------------------------------
    */

    public function getCurrentVisits($request)
    {
        $visits = $this->getPaginatedVisits(
            $request,
            self::ALL_RESULTS
        );

        return $this->success($visits);
    }


    /*
    |--------------------------------------------------------------------------
    | User Visits
    |--------------------------------------------------------------------------
    */

    public function getVisitsByUserId($request)
    {
        $userId = $request->input('userId');

        $startDate = $this->resolveMonthBoundary(
            $request,
            'search_date',
            'Y-m-01',
            fn () => now()->startOfMonth()->toDateString()
        );

        $endDate = $this->resolveMonthBoundary(
            $request,
            'search_date',
            'Y-m-t',
            fn () => now()->endOfMonth()->toDateString()
        );

        $user = User::find($userId);

        if (!$user) {
            return $this->failure('data_not_found');
        }

        $query = $this->buildVisitsQuery($request)
            ->where('visits.user_id', $userId);

        $visits = $this->paginateOrAll(
            $query,
            $request,
            self::DEFAULT_PER_PAGE
        );

        $visitStatistics = (clone $this->DrawVisitStatistics())
            // ->whereDate('visits.visit_date', '>=', $startDate)
            // ->whereDate('visits.visit_date', '<=', $endDate)
            ->where('users.id', $userId)
            ->groupBy('users.id')
            ->first();

        return $this->success([
            'visit_statistics' => new VisitStatisticsResource(
                $visitStatistics
            ),

            'data' => VisitsResource::collection(
                $visits
            ),

            'user' => new UserResource($user),

            'currentDate' => $startDate,
        ]);
    }


    public function getAllVisitsByUserId($request)
    {
        $userId = $request->input(
            'user_id',
            auth()->id()
        );

        $user = User::find($userId);

        if (!$user) {
            return $this->failure('data_not_found');
        }

        $query = $this->buildVisitsQuery($request)
            ->where('visits.user_id', $userId);

        $visits = $this->paginateOrAll(
            $query,
            $request,
            self::DEFAULT_PER_PAGE
        );

        return $this->success([
            'data' => VisitsResource::collection($visits),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Visit Detail
    |--------------------------------------------------------------------------
    */

    protected function buildVisitDetailData(
        Visit $visit
    ): array {
        $visit->load([
            'user:id,name',
            'doubleVisit:id,name',
            'account:id,name',

            'customer' => function ($q) {
                $q->select(
                    'id',
                    'name',
                    'image',
                    'specialty_id',
                    'account_id',
                    'class_id'
                )->with([
                    'account:id,address,lat,lng',
                    'specialty:id,name',
                    'class:id,name'
                ]);
            },
        ]);

        $user = User::find($visit->user_id);

        $products = $this->mergeDataById(
            $this->getUserProducts($user),
            $this->getVisitItemList($visit, 0)
        );

        $leaveBehind = $this->mergeDataById(
            $this->getGifts(GiftTypeEnum::LeaveBehind),
            $this->getVisitItemList(
                $visit,
                GiftTypeEnum::LeaveBehind
            )
        );

        $gifts = $this->mergeDataById(
            $this->getGifts(GiftTypeEnum::Gift),
            $this->getVisitItemList(
                $visit,
                GiftTypeEnum::Gift
            )
        );

        $additionalFiles = $this->mergeDataById(
            $this->getUserProductFiles($user),
            $this->getVisitItemList(
                $visit,
                GiftTypeEnum::AdditionalFiles
            )
        );

        return [
            'visit' => new VisitDetailResource($visit),

            'products' => VisitAccessoryResource::collection(
                $products
            ),

            'leaveBehind' => VisitAccessoryResource::collection(
                $leaveBehind
            ),

            'Gifts' => VisitAccessoryResource::collection(
                $gifts
            ),

            'AdditionalFiles' => VisitAccessoryResource::collection(
                $additionalFiles
            ),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Visit Creation
    |--------------------------------------------------------------------------
    */

    public function createUnplannedVisit($request)
    {
        $currentPlan = User::getCurrentPlan();

        if (!$currentPlan) {
            return $this->failure(
                __('messages.no_active_plan')
            );
        }

        $visitDate = now()->toDateString();
        $startTime = now();
        $endTime = $startTime->copy()->addMinutes(15);

        $combineWith = $this->resolveCombineWith(
            $request->combine_with ?? null
        );

        $attributes = [
            'plan_id' => $currentPlan->id,
            'user_id' => auth()->id(),
            'account_id' => $request->account_id,
            'customer_id' => $request->doctor_id ?? 0,
            'visit_date' => $visitDate,
        ];

        $visit = Visit::updateOrCreate(
            $attributes,
            array_merge($attributes, [
                'type' => 1,
                'combine_with' => $combineWith,
                'start_time' => $startTime->format('H:i:s'),
                 'end_time' => $endTime->format('H:i:s'),
            ])
        );

        return $this->getvisitDtail($visit->id);
    }


    public function submitVisit($request)
    {
        try {

            DB::beginTransaction();

            $visit = Visit::findOrFail(
                $request->visit_id
            );

            $doctorId = $this->resolveDoctorId(
                $request->doctor_id ?? null,
                $visit->customer_id
            );

            $combineWith = $this->resolveCombineWith(
                $request->combine_with ?? null
            );

            $actualStart = $request->start_time
                ? Carbon::parse($request->start_time)
                : now();

            $actualEnd = $request->end_time
                ? Carbon::parse($request->end_time)
                : now();

            $visit->update([
                'status' => VisitStatusEnum::Visited['id'],

                'actual_start_date' =>
                    $actualStart->toDateTimeString(),

                'actual_end_date' =>
                    $actualEnd->toDateTimeString(),

                'customer_id' => $doctorId,

                'combine_with' => $combineWith,

                'user_location_lat' =>
                    $request->current_location_lat,

                'user_location_lng' =>
                    $request->current_location_lng,

                'notes' => $request->notes,
            ]);

            $this->replaceVisitDetails(
                $visit,
                $request->items ?? []
            );

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error(
                'Visit submission failed',
                [
                    'visit_id' => $request->visit_id ?? null,
                    'exception' => $e->getMessage(),
                ]
            );

            return $this->failure('server_error');
        }

        $this->notifications->sendNewVisitCreated(
            $visit,
            auth()->user()
        );

        return [
            'status' => true,
            'message' => trans('messages.visit_success'),
        ];
    }

    /**
 * Update an already visited visit.
 *
 * Editable fields:
 * - notes
 * - combine_with
 * - items
 */
    public function updateVisitedVisit($request)
{
    $visit = Visit::find($request->visit_id);

    if (!$visit) {
        return $this->failure('data_not_found');
    }

    if ((int) $visit->user_id !== (int) auth()->id()) {
        return $this->failure('unauthorized');
    }

    if ((int) $visit->status !== (int) VisitStatusEnum::Visited['id']) {
        return $this->failure('visit_not_visited');
    }

    try {
        DB::beginTransaction();

        $visit->update([
            'notes' => $request->input('notes'),

            'combine_with' => $this->resolveCombineWith(
                $request->input('combine_with')
            ),
        ]);

        $this->replaceVisitDetails(
            $visit,
            $request->items ?? []
        );

        DB::commit();

        $visit->refresh();

        return $this->success(
            $this->buildVisitDetailData($visit)
        );

    } catch (\Throwable $e) {

        DB::rollBack();

        Log::error(
            'Visited visit update failed',
            [
                'visit_id' => $request->input('visit_id'),
                'user_id' => auth()->id(),
                'exception' => $e->getMessage(),
            ]
        );

        return $this->failure('server_error');
    }
}

    /*
    |--------------------------------------------------------------------------
    | Visit Details / Products / Gifts
    |--------------------------------------------------------------------------
    */

    protected function replaceVisitDetails(
        Visit $visit,
        array $items = []
    ): void {
        $visit->visitdetails()->delete();

        if (empty($items)) {
            return;
        }

        $rows = array_map(
            fn ($item) => [
                'visit_id' => $visit->id,
                'item_id' => $item['item_id'],
                'count_of_sample' => $item['sample'] ?? 0,
                'item_type' => $item['item_type'],
                'created_at' => now(),
            ],
            $items
        );

        VisitDetails::insert($rows);
    }


    protected function getUserProducts(User $user)
    {
        return $user->products()
            ->selectRaw(
                '
                products.id,
                products.name,
                products.image as file,
                0 as count_of_sample,
                0 as checked,
                0 as type,
                products.price
                '
            )
            ->get()
            ->keyBy('id');
    }


    protected function getUserProductFiles(User $user)
    {
        return $user->products()
            ->join(
                'product_files',
                'products.id',
                '=',
                'product_files.product_id'
            )
            ->whereNull('product_files.deleted_at')
            ->selectRaw(
                '
                products.id,
                SUBSTRING(products.name, 1, 20) as name,
                product_files.file as file,
                0 as count_of_sample,
                0 as checked,
                3 as type
                '
            )
            ->get()
            ->keyBy('id');
    }


    protected function getGifts(
        $type = GiftTypeEnum::Gift
    ) {
        return Gift::selectRaw(
            '
            id,
            name,
            0 as count_of_sample,
            0 as checked,
            type
            '
        )
        ->where('type', $type)
        ->get()
        ->keyBy('id');
    }


    protected function getVisitItemList(
        Visit $visit,
        $type = 0
    ) {
        return VisitDetails::where(
            'visit_id',
            $visit->id
        )
        ->where('item_type', $type)
        ->selectRaw(
            '
            item_id as id,
            count_of_sample,
            1 as checked
            '
        )
        ->get()
        ->keyBy('id');
    }


    public function mergeDataById(
        Collection ...$collections
    ) {
        $data = [];

        foreach ($collections as $collection) {

            foreach ($collection as $id => $item) {

                if (!$item instanceof Collection) {
                    $item = collect($item);
                }

                $data[$id] = ReportData::make(
                    array_merge(
                        isset($data[$id])
                            ? $data[$id]->toArray()
                            : ['id' => $id],

                        $item->toArray()
                    )
                );
            }
        }

        return collect($data)
            ->sortBy('id')
            ->values();
    }


    public function mergeDataByAccountId(
        Collection ...$collections
    ) {
        $data = [];

        foreach ($collections as $collection) {

            foreach ($collection as $id => $item) {

                if (!$item instanceof Collection) {
                    $item = collect($item);
                }

                $data[$id] = ReportData::make(
                    array_merge(
                        isset($data[$id])
                            ? $data[$id]->toArray()
                            : ['account_id' => $id],

                        $item->toArray()
                    )
                );
            }
        }

        return collect($data)
            ->sortBy('account_id')
            ->values();
    }


    /*
    |--------------------------------------------------------------------------
    | Statistics
    |--------------------------------------------------------------------------
    */

    public function getVisitCharts($request)
    {
        [$searchDate, $formatDate] =
            $this->prepareDateParams($request);

        $query = $this->DrawVisitStatistics();

        if ($searchDate) {
            $this->applyDateFilter(
                $query,
                $searchDate,
                $formatDate
            );
        }

        $query->when(
            $request->filled('search'),
            fn ($q) => $q->where(
                'users.name',
                'like',
                '%' . $request->search . '%'
            )
        );

        $charts = $query
            ->selectRaw(
                'users.id, users.name, COUNT(*) as visit_count'
            )
            ->where(
                'visits.status',
                VisitStatusEnum::Visited
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('visit_count')
            ->limit(10)
            ->get();

        return $this->success($charts);
    }


    public function getAllVisits()
    {
        $request = request();

        $search = $request->filled('search_date')
            ? Carbon::parse($request->search_date)
            : now();

        $startDate = $search
            ->copy()
            ->startOfMonth()
            ->toDateString();

        $endDate = $search
            ->copy()
            ->endOfMonth()
            ->toDateString();

        $query = $this->DrawVisitStatistics()
            ->whereBetween(
                'visits.visit_date',
                [$startDate, $endDate]
            )
            ->groupBy(
                'users.id',
                'users.name'
            )
            ->orderByDesc('visit_count');

        $visits = $this->paginateOrAll(
            $query,
            $request,
            self::DEFAULT_PER_PAGE
        );

        return $this->success(
            VisitStatisticsResource::collection($visits)
        );
    }


    public function getUserVisitStatictics($request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse(
                $request->start_date
            )->format('Y-m-d')
            : null;

        $endDate = $request->filled('end_date')
            ? Carbon::parse(
                $request->end_date
            )->format('Y-m-d')
            : null;

        $userId = $request->input('user_id');

        $rows = Visit::join(
            'accounts',
            'visits.account_id',
            '=',
            'accounts.id'
        )
        ->join(
            'customers',
            'visits.customer_id',
            '=',
            'customers.id'
        )
        ->leftJoin(
            'specialty',
            'customers.specialty_id',
            '=',
            'specialty.id'
        )
        ->leftJoin(
            'classes',
            'customers.class_id',
            '=',
            'classes.id'
        )
        ->join(
            'user_customers',
            function ($join) use ($userId) {

                $join->on(
                    'user_customers.account_id',
                    '=',
                    'accounts.id'
                )
                ->where(
                    'user_customers.user_id',
                    $userId
                );
            }
        )
        ->when(
            $startDate,
            fn ($q, $v) => $q->whereDate(
                'visits.visit_date',
                '>=',
                $v
            )
        )
        ->when(
            $endDate,
            fn ($q, $v) => $q->whereDate(
                'visits.visit_date',
                '<=',
                $v
            )
        )
        ->where(
            'visits.user_id',
            $userId
        )
        ->where(
            'visits.status',
            VisitStatusEnum::Visited['id']
        )
        ->groupBy(
            'accounts.id',
            'customers.id'
        )
        ->select([
            'accounts.id as account_id',
            'accounts.name as account_name',
            'customers.id as doctor_id',
            'customers.name as doctor_name',
            'specialty.name as specialty_name',
            'classes.name as class_name',
            DB::raw(
                'COUNT(DISTINCT visits.id) as visits_count'
            ),
            DB::raw(
                "GROUP_CONCAT(
                    DISTINCT DATE_FORMAT(
                        visits.visit_date,
                        '%Y-%m-%d'
                    )
                    ORDER BY visits.visit_date DESC
                ) AS visit_dates"
            ),
        ])
        ->get();

        $byAccount = $rows
            ->groupBy('account_id')
            ->map(fn ($group) => [
                'account_name' =>
                    $group->first()->account_name,

                'total_visits' =>
                    $group->sum('visits_count'),

                'doctors' => $group
                    ->map(fn ($row) => [
                        'doctor_id' =>
                            $row->doctor_id,

                        'doctor_name' =>
                            $row->doctor_name,

                        'specialty_name' =>
                            $row->specialty_name,

                        'class_name' =>
                            $row->class_name,

                        'visits_count' =>
                            $row->visits_count,

                        'visit_dates' =>
                            explode(
                                ',',
                                $row->visit_dates
                            ),
                    ])
                    ->values(),
            ])
            ->values();

        $bySpecialty = Visit::join(
            'customers',
            'visits.customer_id',
            '=',
            'customers.id'
        )
        ->join(
            'specialty',
            'customers.specialty_id',
            '=',
            'specialty.id'
        )
        ->when(
            $startDate,
            fn ($q, $v) => $q->whereDate(
                'visits.visit_date',
                '>=',
                $v
            )
        )
        ->when(
            $endDate,
            fn ($q, $v) => $q->whereDate(
                'visits.visit_date',
                '<=',
                $v
            )
        )
        ->where(
            'visits.user_id',
            $userId
        )
        ->where(
            'visits.status',
            VisitStatusEnum::Visited['id']
        )
        ->groupBy('specialty.id')
        ->select(
            'specialty.name as specialty_name',
            DB::raw(
                'COUNT(DISTINCT visits.id) as total_visits'
            )
        )
        ->get();

        $byClass = Visit::join(
            'customers',
            'visits.customer_id',
            '=',
            'customers.id'
        )
        ->join(
            'classes',
            'customers.class_id',
            '=',
            'classes.id'
        )
        ->when(
            $startDate,
            fn ($q, $v) => $q->whereDate(
                'visits.visit_date',
                '>=',
                $v
            )
        )
        ->when(
            $endDate,
            fn ($q, $v) => $q->whereDate(
                'visits.visit_date',
                '<=',
                $v
            )
        )
        ->where(
            'visits.user_id',
            $userId
        )
        ->where(
            'visits.status',
            VisitStatusEnum::Visited['id']
        )
        ->groupBy('classes.id')
        ->select(
            'classes.name as class_name',
            DB::raw(
                'COUNT(DISTINCT visits.id) as total_visits'
            )
        )
        ->get();

        return $this->success([
            'by_account' => $byAccount,
            'by_specialty' => $bySpecialty,
            'by_class' => $byClass,
        ]);
    }


    public function getUserVisitAndSalesStatictics($request)
    {
        $startDate = $request->input(
            'start_date',
            now()->startOfMonth()->format('Y-m-d')
        );

        $endDate = $request->input(
            'end_date',
            now()->endOfMonth()->format('Y-m-d')
        );

        $userIds = (array) $request->input('user_id');

        $visits = Visit::join(
            'accounts',
            'visits.account_id',
            '=',
            'accounts.id'
        )
        ->join(
            'user_customers',
            function ($join) use ($userIds) {

                $join->on(
                    'user_customers.account_id',
                    '=',
                    'accounts.id'
                )
                ->whereIn(
                    'user_customers.user_id',
                    $userIds
                );
            }
        )
        ->leftJoin(
            'sales',
            function ($join) use (
                $userIds,
                $startDate,
                $endDate
            ) {

                $join->on(
                    'sales.account_id',
                    '=',
                    'accounts.id'
                )
                ->whereIn(
                    'sales.user_id',
                    $userIds
                )
                ->whereDate(
                    'sales.month_date',
                    '>=',
                    $startDate
                )
                ->whereDate(
                    'sales.month_date',
                    '<=',
                    $endDate
                );
            }
        )
        ->whereIn(
            'visits.user_id',
            $userIds
        )
        ->where(
            'visits.status',
            VisitStatusEnum::Visited['id']
        )
        ->whereDate(
            'visits.visit_date',
            '>=',
            $startDate
        )
        ->whereDate(
            'visits.visit_date',
            '<=',
            $endDate
        )
        ->select(
            'accounts.name as account_name',
            DB::raw(
                'COUNT(DISTINCT visits.id) as total_visits'
            ),
            DB::raw(
                'COALESCE(
                    SUM(sales.total_price),
                    0
                ) as total_sales'
            )
        )
        ->groupBy('accounts.name')
        ->get();

        return $this->success([
            'by_account' => $visits,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Charts
    |--------------------------------------------------------------------------
    */

    public function DrawVisitStatistics()
    {
        return Visit::query()
            ->selectRaw("
                users.id,
                users.name,

                SUM(
                    CASE
                        WHEN visits.status = 2
                        THEN 1 ELSE 0
                    END
                ) AS visit_count,

                SUM(
                    CASE
                        WHEN visits.type = 0
                        THEN 1 ELSE 0
                    END
                ) AS pln_visit_count,

                SUM(
                    CASE
                        WHEN visits.type = 1
                        THEN 1 ELSE 0
                    END
                ) AS unpln_visit_count,

                SUM(
                    CASE
                        WHEN visits.status = 5
                        THEN 1 ELSE 0
                    END
                ) AS missed_visit_count,

                SUM(
                    CASE
                        WHEN visits.status = 0
                        THEN 1 ELSE 0
                    END
                ) AS pending_count
            ")
            ->join(
                'users',
                'users.id',
                '=',
                'visits.user_id'
            )
            ->join(
                'plans',
                'plans.id',
                '=',
                'visits.plan_id'
            );
    }


    public function DrawVisitCountStatistics()
    {
        return Visit::selectRaw(
            '
            visits.account_id,
            count(visits.id) as visit_count
            '
        )
        ->join(
            'users',
            'users.id',
            '=',
            'visits.user_id'
        )
        ->join(
            'plans',
            'plans.id',
            '=',
            'visits.plan_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function joinAccountsAndCustomers($query)
    {
        return $query
            ->join(
                'accounts',
                'accounts.id',
                '=',
                'visits.account_id'
            )
            ->leftJoin(
                'customers',
                'customers.id',
                '=',
                'visits.customer_id'
            );
    }


    protected function resolveDoctorId(
        $doctorId,
        $fallback
    ) {
        return (
            isset($doctorId)
            && is_numeric($doctorId)
            && $doctorId > 0
        )
            ? $doctorId
            : $fallback;
    }


    protected function resolveCombineWith($combineWith)
    {
        return (
            isset($combineWith)
            && is_numeric($combineWith)
            && $combineWith > 0
        )
            ? $combineWith
            : 0;
    }


    protected function resolveMonthBoundary(
        $request,
        string $field,
        string $format,
        \Closure $default
    ): string {
        $value = $request->{$field} ?? null;

        return !empty($value)
            ? Carbon::parse($value)->format($format)
            : $default();
    }


    protected function prepareDateParams($request): array
    {
        $date = $request->filled('search_date')
            ? Carbon::parse($request->search_date)
            : now();

        $format = match (
            $request->input('date_format')
        ) {
            'YYYY' => 'Y',
            'YYYY-MM-DD' => 'Y-m-d',
            default => 'Y-m',
        };

        return [$date, $format];
    }


    private function applyDateFilter(
        $query,
        Carbon $date,
        string $format
    ): void {
        match ($format) {

            'Y-m' => $query
                ->whereYear(
                    'visits.visit_date',
                    $date->year
                )
                ->whereMonth(
                    'visits.visit_date',
                    $date->month
                ),

            'Y' => $query
                ->whereYear(
                    'visits.visit_date',
                    $date->year
                ),

            'Y-m-d' => $query
                ->whereDate(
                    'visits.visit_date',
                    $date->toDateString()
                ),
        };
    }


    protected function getSetting()
    {
        return Setting::first();
    }


    /*
    |--------------------------------------------------------------------------
    | Distance
    |--------------------------------------------------------------------------
    */

    public function getDistance(
        $latitude1,
        $longitude1,
        $latitude2,
        $longitude2
    ) {
        $earthRadius = 6371;

        $dLat = deg2rad(
            $latitude2 - $latitude1
        );

        $dLon = deg2rad(
            $longitude2 - $longitude1
        );

        $a =
            sin($dLat / 2) ** 2
            +
            cos(deg2rad($latitude1))
            *
            cos(deg2rad($latitude2))
            *
            sin($dLon / 2) ** 2;

        $c = 2 * asin(sqrt($a));

        return $earthRadius * $c;
    }


    /*
    |--------------------------------------------------------------------------
    | Offline Visits
    |--------------------------------------------------------------------------
    */

    public function submitOfflineVisits($request)
    {
        try {

            DB::beginTransaction();

            $results = [];
            $notifications = [];

            foreach ($request->visits as $offlineVisit) {

                if (empty($offlineVisit['visit_id'])) {
                    throw new \Exception(
                        'visit_id is required for each offline visit'
                    );
                }

                if (
                    empty($offlineVisit['start_time'])
                    ||
                    empty($offlineVisit['end_time'])
                ) {
                    throw new \Exception(
                        "start_time and end_time are required for visit {$offlineVisit['visit_id']}"
                    );
                }

                $visit = Visit::find(
                    $offlineVisit['visit_id']
                );

                if (!$visit) {
                    throw new \Exception(
                        "Visit {$offlineVisit['visit_id']} not found"
                    );
                }

                if (
                    (int) $visit->user_id
                    !==
                    (int) auth()->id()
                ) {
                    throw new \Exception(
                        "Visit {$visit->id} does not belong to current user"
                    );
                }

                $wasAlreadyVisited =
                    (int) $visit->status
                    ===
                    (int) VisitStatusEnum::Visited;

                $doctorId = $this->resolveDoctorId(
                    $offlineVisit['doctor_id'] ?? null,
                    $visit->customer_id
                );

                $combineWith = $this->resolveCombineWith(
                    $offlineVisit['combine_with'] ?? null
                );

                $startTime = Carbon::parse(
                    $offlineVisit['start_time']
                );

                $endTime = Carbon::parse(
                    $offlineVisit['end_time']
                );

                $data = [
                    'status' =>
                        VisitStatusEnum::Visited['id'],

                    'actual_start_date' =>
                        $startTime,

                    'actual_end_date' =>
                        $endTime,

                    'customer_id' =>
                        $doctorId,

                    'combine_with' =>
                        $combineWith,

                    'user_location_lat' =>
                        $offlineVisit['current_location_lat'] ?? null,

                    'user_location_lng' =>
                        $offlineVisit['current_location_lng'] ?? null,

                    'notes' =>
                        $offlineVisit['notes'] ?? null,
                ];

                if ((int) $visit->type === 1) {

                    $data['visit_date'] =
                        $startTime->toDateString();

                    $data['start_time'] =
                        $startTime->format('H:i:s');

                    $data['end_time'] =
                        $endTime->format('H:i:s');
                }

                $visit->update($data);

                $this->replaceVisitDetails(
                    $visit,
                    $offlineVisit['items'] ?? []
                );

                $results[] = [
                    'visit_id' => $visit->id,
                    'status' => 'synced',
                ];

                if (!$wasAlreadyVisited) {
                    $notifications[] = $visit->fresh();
                }
            }

            DB::commit();

            foreach ($notifications as $visit) {
                $this->notifications->sendNewVisitCreated(
                    $visit,
                    auth()->user()
                );
            }

            return $this->success([
                'message' =>
                    'Offline visits synced successfully',

                'data' => $results,
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error(
                'Offline visits submission failed',
                [
                    'user_id' => auth()->id(),
                    'exception' => $e->getMessage(),
                    'visits' =>
                        $request->visits ?? [],
                ]
            );

            return $this->failure(
                'server_error'
            );
        }
    }
}


/**
 * Helper collection used by visit accessory merging.
 */
class ReportData extends Collection
{
    public function __get($name)
    {
        return $this->get($name);
    }
}
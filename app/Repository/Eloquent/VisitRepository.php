<?php

namespace App\Repository\Eloquent;

use App\Repository\Interfaces\VisitInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Models\Plan;
use App\Models\User;
use App\Models\Visit;
use App\Models\Gift;
use App\Models\Setting;
use App\Http\Resources\API\VisitDetailResource;
use App\Http\Resources\API\VisitsResource;
use App\Http\Resources\API\UserResource;
use App\Http\Resources\API\VisitStatisticsResource;
use App\Enums\GiftTypeEnum;
use App\Enums\VisitStatusEnum;
use App\Models\VisitDetails;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VisitRepository implements VisitInterface
{
    protected const DEFAULT_PER_PAGE = 20;

    // Used by getvisitsByPlan when no per_page is given: effectively "all".
    protected const NO_LIMIT_PER_PAGE = 100000;

    // User.position values (rep can't see plans/visits that aren't theirs yet).
    protected const POSITION_REP = 3;

    protected NotificationService $notifications;

    public function __construct(NotificationService $notifications)
    {
        $this->notifications = $notifications;
    }

    public function getvisitsByPlan($request)
    {
        $limit = $this->resolvePerPage($request, self::NO_LIMIT_PER_PAGE);
        $request->plan_id = $request->plan_id ?? User::getCurrentPlan()?->id;

        $plan = Plan::find($request->plan_id);

        if (!$plan) {
            return $this->success([]);
        }

        $isRep = auth()->user()->position == self::POSITION_REP;

        if ($plan->status == 0 && $isRep) {
            return ['status' => true, 'message' => trans('messages.plan_reviewed'), 'data' => []];
        }

        if ($plan->status == 2 && $isRep) {
            return ['status' => true, 'message' => trans('messages.plan_rejected'), 'data' => []];
        }

        $visits = $this->joinAccountsAndCustomers($plan->visits())
            ->select('visits.*')
            ->filter($request)
            ->paginate($limit);

        return $this->success(VisitsResource::collection($visits));
    }

    public function getvisitDtail($id)
    {
        $visit = Visit::find($id);

        if (!$visit) {
            return $this->failure('data_not_found');
        }

        return $this->success($this->buildVisitDetailData($visit));
    }

    /**
     * Manager equivalent of getvisitsByPlan: lists visits belonging to any
     * of the manager's subordinates rather than a single plan.
     */
    public function getVisitsForManager($request, array $subordinateIds)
    {
         $limit = $this->resolvePerPage($request, self::NO_LIMIT_PER_PAGE);
         $request->plan_id = $request->plan_id ?? '';

        $visits = $this->joinAccountsAndCustomers(
                Visit::select('visits.*')->whereIn('visits.user_id', $subordinateIds)
            )
            ->filter($request)
            ->orderBy('visits.created_at', 'DESC')
            ->paginate($limit);

        return $this->success(VisitsResource::collection($visits));
    }

    /**
     * Manager equivalent of getvisitDtail: only returns the visit if it
     * belongs to one of the manager's subordinates.
     */
    public function showVisitForManager($id, array $subordinateIds)
    {
        $visit = Visit::whereIn('user_id', $subordinateIds)->find($id);

        if (!$visit) {
            return $this->failure('data_not_found');
        }

        return $this->success($this->buildVisitDetailData($visit));
    }

    /**
     * Builds the "visit detail" payload (visit + products / leave-behind /
     * gifts / additional files) shared by both the rep-facing and
     * manager-facing detail endpoints.
     */
    protected function buildVisitDetailData(Visit $visit): array
    {
        $user = User::find($visit->user_id);

        $products = $this->mergeDataById(
            $this->getUserProducts($user),
            $this->getVisitItemList($visit, 0) // type -- products
        );

        $leaveBehind = $this->mergeDataById(
            $this->getGifts(GiftTypeEnum::LeaveBehind),
            $this->getVisitItemList($visit, GiftTypeEnum::LeaveBehind)
        );

        $gifts = $this->mergeDataById(
            $this->getGifts(GiftTypeEnum::Gift),
            $this->getVisitItemList($visit, GiftTypeEnum::Gift)
        );

        $additionalFiles = $this->mergeDataById(
            $this->getUserProductFiles($user),
            $this->getVisitItemList($visit, GiftTypeEnum::AdditionalFiles)
        );

        return [
            'visit'           => new VisitsResource($visit),
            'products'        => VisitDetailResource::collection($products),
            'leaveBehind'     => VisitDetailResource::collection($leaveBehind),
            'Gifts'           => VisitDetailResource::collection($gifts),
            'AdditionalFiles' => VisitDetailResource::collection($additionalFiles),
        ];
    }

    protected function getUserProducts(User $user)
    {
        return $user->products()
            ->selectRaw('products.id , products.name ,products.image as file ,0 as count_of_sample , 0 as checked , 0 as type,products.price')
            ->get()
            ->keyBy('id');
    }

    protected function getUserProductFiles(User $user)
    {
        return $user->products()
            ->whereHas('productfiles', function ($q) {
                $q->whereNull('product_files.deleted_at');
            })
            ->selectRaw('products.id ,SUBSTRING(products.name, 1, 20) as name ,0 as count_of_sample , 0 as checked , 3 as type')
            ->get();
    }

    protected function getGifts($type = GiftTypeEnum::Gift)
    {
        return Gift::selectRaw('id , name ,0 as count_of_sample , 0 as checked ,type')
            ->where('type', $type)
            ->get();
    }

    protected function getVisitItemList(Visit $visit, $type = 0)
    {
        return $visit->visitdetails()
            ->selectRaw('item_id as id ,count_of_sample, 1 as checked ')
            ->where('item_type', $type)
            ->get()
            ->keyBy('id');
    }

    public function mergeDataById(Collection ...$collections)
    {
        $data = [];

        foreach ($collections as $collection) {
            foreach ($collection as $id => $item) {
                if (!$item instanceof Collection) {
                    $item = collect($item);
                }

                $data[$id] = ReportData::make(array_merge(
                    isset($data[$id]) ? $data[$id]->toArray() : ['id' => $id],
                    $item->toArray()
                ));
            }
        }

        return collect($data)->sortBy('id', SORT_REGULAR, false)->values();
    }

    public function mergeDataByAccountId(Collection ...$collections)
    {
        $data = [];

        foreach ($collections as $collection) {
            foreach ($collection as $id => $item) {
                if (!$item instanceof Collection) {
                    $item = collect($item);
                }

                $data[$id] = ReportData::make(array_merge(
                    isset($data[$id]) ? $data[$id]->toArray() : ['account_id' => $id],
                    $item->toArray()
                ));
            }
        }

        return collect($data)->sortBy('account_id', SORT_REGULAR, false)->values();
    }

    public function createUnplannedVisit($request)
    {
        $currentPlanId = User::getCurrentPlan()?->id;
        $visitDate = Carbon::now()->toDateString();

        $attributes = [
            'plan_id'     => $currentPlanId,
            'user_id'     => auth()->user()->id,
            'account_id'  => $request->account_id,
            'customer_id' => $request->doctor_id ?? 0,
            'visit_date'  => $visitDate,
        ];

        $createdVisit = Visit::updateOrCreate($attributes, array_merge($attributes, ['type' => 1]));

        return $this->getvisitDtail($createdVisit->id);
    }

    public function submitVisit($request)
    {
        try {
            DB::beginTransaction();

            $visit = Visit::findOrFail($request->visit_id);

            $doctorId = (isset($request->doctor_id) && is_numeric($request->doctor_id) && $request->doctor_id > 0)
                ? $request->doctor_id
                : $visit->customer_id;

            $combineWith = (isset($request->combine_with) && is_numeric($request->combine_with) && $request->combine_with > 0)
                ? $request->combine_with
                : 0;

            $data = [
                'status'             => (VisitStatusEnum::Visited)['id'],
                'actual_start_date'  => Carbon::now()->toDateTimeString(),
                'actual_end_date'    => Carbon::now()->toDateTimeString(),
                'customer_id'        => $doctorId,
                'combine_with'       => $combineWith,
                'user_location_lat'  => $request->current_location_lat,
                'user_location_lng'  => $request->current_location_lng,
                'notes'              => $request->notes,
            ];

            // Unplanned visits don't already have a date/time, so they're
            // filled in from the submission itself.
            if ($visit->type == 1) {
                $data = array_merge($data, [
                    'visit_date' => Carbon::parse($request->start_time)->toDateString(),
                    'start_time' => $request->start_time ? Carbon::parse($request->start_time)->format('H:i:s') : Carbon::now()->format('H:i:s'),
                    'end_time'   => $request->end_time ? Carbon::parse($request->end_time)->format('H:i:s') : Carbon::now()->format('H:i:s'),
                ]);
            }

            $createdVisit = Visit::updateOrCreate(['id' => $visit->id], $data);

            $this->replaceVisitDetails($createdVisit, $request->items);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Visit submission failed: ' . $e->getMessage(), [
                'visit_id'  => $request->visit_id ?? null,
                'exception' => $e,
            ]);

            return $this->failure('server_error');
        }

        // Fired after commit: a notification failure should never undo a
        // visit that was actually saved successfully.
        $this->notifications->sendNewVisitCreated($createdVisit, auth()->user());

        return ['status' => true, 'message' => trans('messages.visit_success')];
    }

    /**
     * Replaces a visit's product/gift/leave-behind/file selections with
     * whatever was submitted this time.
     */
    protected function replaceVisitDetails(Visit $visit, array $items): void
    {
        $rows = array_map(fn ($item) => [
            'visit_id'        => $visit->id,
            'item_id'         => $item['item_id'],
            'count_of_sample' => $item['sample'],
            'item_type'       => $item['item_type'],
            'created_at'      => Carbon::now(),
        ], $items);

        $visit->visitdetails()->delete();

        if (!empty($rows)) {
            VisitDetails::insert($rows);
        }
    }

    /**
     * Haversine distance in kilometers between two lat/lng points.
     */
    public function getDistance($latitude1, $longitude1, $latitude2, $longitude2)
    {
        $earthRadius = 6371;

        $dLat = deg2rad($latitude2 - $latitude1);
        $dLon = deg2rad($longitude2 - $longitude1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * asin(sqrt($a));

        return $earthRadius * $c;
    }

    protected function getSetting()
    {
        return Setting::first();
    }

    public function getVisitCharts($request)
    {
        [$searchDate, $formatDate] = $this->prepareDateParams($request);

        $query = $this->DrawVisitStatistics();

        if ($searchDate) {
            $this->applyDateFilter($query, $searchDate, $formatDate);
        }

        if ($request->filled('search')) {
            $query->where('users.name', 'like', '%' . $request->search . '%');
        }

        $charts = $query
            ->selectRaw('users.id, users.name, COUNT(*) as visit_count')
            ->where('visits.status', VisitStatusEnum::Visited)
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('visit_count')
            ->limit(3)
            ->get();

        return $this->success($charts);
    }

    private function prepareDateParams($request): array
    {
        $date = $request->filled('search_date')
            ? Carbon::parse($request->search_date)
            : now();

        $format = match ($request->input('date_format')) {
            'YYYY'       => 'Y',
            'YYYY-MM-DD' => 'Y-m-d',
            default      => 'Y-m', // matches YYYY-MM
        };

        return [$date, $format];
    }

    private function applyDateFilter($query, Carbon $date, string $format): void
    {
        switch ($format) {
            case 'Y-m': // full month
                $query->whereYear('visits.visit_date', $date->year)
                    ->whereMonth('visits.visit_date', $date->month);
                break;

            case 'Y': // full year
                $query->whereYear('visits.visit_date', $date->year);
                break;

            case 'Y-m-d': // specific day
                $query->whereDate('visits.visit_date', $date->toDateString());
                break;
        }
    }

    public function getAllVisits()
    {
        $request = request();

        if ($request->filled('search_date')) {
            $search = Carbon::parse($request->search_date);
            $startDate = $search->copy()->startOfMonth()->toDateString();
            $endDate = $search->copy()->endOfMonth()->toDateString();
        } else {
            $startDate = now()->startOfMonth()->toDateString();
            $endDate = now()->endOfMonth()->toDateString();
        }

        $limit = $this->resolvePerPage($request);

        $visits = $this->DrawVisitStatistics()
            ->whereBetween('visits.visit_date', [$startDate, $endDate])
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('visit_count')
            ->paginate($limit);

        return $this->success(VisitStatisticsResource::collection($visits));
    }

    public function DrawVisitStatistics()
    {
        return Visit::query()
            ->selectRaw("
                users.id,
                users.name,
                SUM(CASE WHEN visits.status = 2 THEN 1 ELSE 0 END)                                          AS visit_count,
                SUM(CASE WHEN visits.type   = 0 THEN 1 ELSE 0 END)                                          AS pln_visit_count,
                SUM(CASE WHEN visits.type   = 1 THEN 1 ELSE 0 END)                                          AS unpln_visit_count,
                SUM(CASE WHEN visits.status != 2 AND DATE(visits.visit_date) < CURDATE() THEN 1 ELSE 0 END) AS missed_visit_count,
                SUM(CASE WHEN visits.status = 0  AND DATE(visits.visit_date) > CURDATE() THEN 1 ELSE 0 END) AS pending_count
            ")
            ->join('users', 'users.id', '=', 'visits.user_id')
            ->join('plans', 'plans.id', '=', 'visits.plan_id');
    }

    public function DrawVisitCountStatistics()
    {
        return Visit::selectRaw('visits.account_id , count(visits.id) as visit_count')
            ->join('users', 'users.id', '=', 'visits.user_id')
            ->join('plans', 'plans.id', '=', 'visits.plan_id');
    }

    public function getVisitsByUserId($request) // monthly
    {
        $userId = $request->userId ?? auth()->user()->id;
        $startDate = $this->resolveMonthBoundary($request, 'search_date', 'Y-m-01', fn () => Carbon::now()->startOfMonth()->toDateString());
        $endDate = $this->resolveMonthBoundary($request, 'search_date', 'Y-m-t', fn () => Carbon::now()->endOfMonth()->toDateString());

        $user = User::find($userId);

        if (!$user) {
            return $this->failure('data_not_found');
        }

        $limit = $this->resolvePerPage($request);

        $visits = $this->joinAccountsAndCustomers(
                $user->visits()->join('plans', 'plans.id', '=', 'visits.plan_id')
            )
            ->selectRaw('visits.*')
            ->filter($request)
            ->paginate($limit);

        $visitStatistics = (clone $this->DrawVisitStatistics())
            ->whereDate('visits.visit_date', '>=', $startDate)
            ->whereDate('visits.visit_date', '<=', $endDate)
            ->where('users.id', $userId)
            ->groupBy('users.id')
            ->first();

        $data = [
            'visit_statistics' => new VisitStatisticsResource($visitStatistics),
            'data'             => VisitsResource::collection($visits),
            'user'             => new UserResource($user),
            'currentDate'      => $startDate,
        ];

        return $this->success($data);
    }

    public function getAllVisitsByUserId($request)
    {
        $userId = $request->userId ?? auth()->user()->id;
        $user = User::find($userId);

        if (!$user) {
            return $this->failure('data_not_found');
        }

        $limit = $this->resolvePerPage($request);

        $visits = $user->visits()
            ->join('plans', 'plans.id', '=', 'visits.plan_id')
            ->filter($request)
            ->paginate($limit);

        return $this->success(['data' => VisitsResource::collection($visits)]);
    }

    public function getCurrentVisits()
    {
        $request = request();
        $limit = $this->resolvePerPage($request, self::NO_LIMIT_PER_PAGE);

        $startDate = $request->get('start_date') ?: Carbon::today();
        $endDate = $request->get('end_date') ?: '';

        $visits = Visit::select('visits.*')
            ->join('plans', 'plans.id', '=', 'visits.plan_id')
            ->whereHas('user', fn ($q) => $q->where('users.status', 1))
            ->when($startDate, fn ($q, $v) => $q->whereDate('visits.actual_start_date', '>=', $v))
            ->when($endDate, fn ($q, $v) => $q->whereDate('visits.actual_start_date', '<=', $v))
            ->when($request->get('user_id'), fn ($q, $v) => $q->where('visits.user_id', $v))
            ->where('visits.status', 2)
            ->orderBy('visits.created_at', 'DESC')
            ->paginate($limit);

        return $this->success(['data' => VisitsResource::collection($visits)]);
    }

    public function getUserVisitStatictics($request)
    {
        $startDate = $request->filled('start_date') ? Carbon::parse($request->input('start_date'))->format('Y-m-d') : null;
        $endDate = $request->filled('end_date') ? Carbon::parse($request->input('end_date'))->format('Y-m-d') : null;
        $userId = $request->input('user_id');

        $rows = Visit::join('accounts', 'visits.account_id', '=', 'accounts.id')
            ->join('customers', 'visits.customer_id', '=', 'customers.id')
            ->leftJoin('specialty', 'customers.specialty_id', '=', 'specialty.id')
            ->leftJoin('classes', 'customers.class_id', '=', 'classes.id')
            ->join('user_customers', function ($join) use ($userId) {
                $join->on('user_customers.account_id', '=', 'accounts.id')
                    ->where('user_customers.user_id', '=', $userId);
            })
            ->when($startDate, fn ($q, $v) => $q->whereDate('visits.visit_date', '>=', $v))
            ->when($endDate, fn ($q, $v) => $q->whereDate('visits.visit_date', '<=', $v))
            ->where('visits.user_id', $userId)
            ->where('visits.status', 2)
            ->groupBy('accounts.id', 'customers.id')
            ->select([
                'accounts.id as account_id',
                'accounts.name as account_name',
                'customers.id as doctor_id',
                'customers.name as doctor_name',
                'specialty.name as specialty_name',
                'classes.name as class_name',
                DB::raw('COUNT(DISTINCT visits.id) as visits_count'),
                DB::raw("GROUP_CONCAT(DISTINCT DATE_FORMAT(visits.visit_date, '%Y-%m-%d') ORDER BY visits.visit_date DESC) AS visit_dates"),
            ])
            ->get();

        $byAccount = $rows->groupBy('account_id')->map(fn ($group) => [
            'account_name' => $group->first()->account_name,
            'total_visits' => $group->sum('visits_count'),
            'doctors'      => $group->map(fn ($row) => [
                'doctor_id'      => $row->doctor_id,
                'doctor_name'    => $row->doctor_name,
                'specialty_name' => $row->specialty_name,
                'class_name'     => $row->class_name,
                'visits_count'   => $row->visits_count,
                'visit_dates'    => explode(',', $row->visit_dates),
            ])->values(),
        ])->values();

        $bySpecialty = Visit::join('customers', 'visits.customer_id', '=', 'customers.id')
            ->join('specialty', 'customers.specialty_id', '=', 'specialty.id')
            ->when($startDate, fn ($q, $v) => $q->whereDate('visits.visit_date', '>=', $v))
            ->when($endDate, fn ($q, $v) => $q->whereDate('visits.visit_date', '<=', $v))
            ->where('visits.user_id', $userId)
            ->where('visits.status', 2)
            ->groupBy('specialty.id')
            ->select('specialty.name as specialty_name', DB::raw('COUNT(DISTINCT visits.id) as total_visits'))
            ->get();

        $byClass = Visit::join('customers', 'visits.customer_id', '=', 'customers.id')
            ->join('classes', 'customers.class_id', '=', 'classes.id')
            ->when($startDate, fn ($q, $v) => $q->whereDate('visits.visit_date', '>=', $v))
            ->when($endDate, fn ($q, $v) => $q->whereDate('visits.visit_date', '<=', $v))
            ->where('visits.user_id', $userId)
            ->where('visits.status', 2)
            ->groupBy('classes.id')
            ->select('classes.name as class_name', DB::raw('COUNT(DISTINCT visits.id) as total_visits'))
            ->get();

        return $this->success([
            'by_account'   => $byAccount,
            'by_specialty' => $bySpecialty,
            'by_class'     => $byClass,
        ]);
    }

    public function getUserVisitAndSalesStatictics($request)
    {
        $startDate = $request->input('start_date') ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->input('end_date') ?? now()->endOfMonth()->format('Y-m-d');
        $userIds = (array) $request->input('user_id');

        $visits = Visit::join('accounts', 'visits.account_id', '=', 'accounts.id')
            ->join('user_customers', function ($join) use ($userIds) {
                $join->on('user_customers.account_id', '=', 'accounts.id')
                    ->whereIn('user_customers.user_id', $userIds);
            })
            ->leftJoin('sales', function ($join) use ($userIds, $startDate, $endDate) {
                $join->on('sales.account_id', '=', 'accounts.id')
                    ->whereIn('sales.user_id', $userIds)
                    ->whereDate('sales.month_date', '>=', $startDate)
                    ->whereDate('sales.month_date', '<=', $endDate);
            })
            ->whereIn('visits.user_id', $userIds)
            ->where('visits.status', 2)
            ->whereDate('visits.visit_date', '>=', $startDate)
            ->whereDate('visits.visit_date', '<=', $endDate)
            ->select(
                'accounts.name as account_name',
                DB::raw('COUNT(DISTINCT visits.id) as total_visits'),
                DB::raw('COALESCE(SUM(sales.total_price), 0) as total_sales')
            )
            ->groupBy('accounts.name')
            ->get();

        return $this->success(['by_account' => $visits]);
    }

    /**
     * Shared `join accounts / left join customers` pattern used by several
     * visit-listing queries.
     */
    protected function joinAccountsAndCustomers($query)
    {
        return $query
            ->join('accounts', 'accounts.id', '=', 'visits.account_id')
            ->leftJoin('customers', 'customers.id', '=', 'visits.customer_id');
    }

    protected function resolveMonthBoundary($request, string $field, string $format, \Closure $default): string
    {
        $value = $request->{$field} ?? null;

        return !empty($value) ? Carbon::parse($value)->format($format) : $default();
    }

    protected function resolvePerPage($request, int $default = self::DEFAULT_PER_PAGE): int
    {
        $perPage = $request->per_page ?? request()->get('per_page');

        return (is_numeric($perPage) && $perPage > 0) ? (int) $perPage : $default;
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

class ReportData extends Collection
{
    public function __get($name)
    {
        return $this->get($name, null);
    }
}
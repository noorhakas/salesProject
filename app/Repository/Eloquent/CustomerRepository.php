<?php

namespace App\Repository\Eloquent;

use App\Http\Resources\API\AccountCustomerResource;
use App\Http\Resources\API\CustomerDetailResource;
use App\Http\Resources\API\CustomerResource;
use App\Http\Traits\PaginatesResults;
use App\Models\AccType;
use App\Models\Account;
use App\Models\Customer;
use App\Models\Specialty;
use App\Repository\Interfaces\CustomerInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerRepository implements CustomerInterface
{
    use PaginatesResults;

    /**
     * Get all customers.
     */
    public function getAll($request)
    {
        $query = $this->getCustomerQuery();

        $query = $this->applyCustomerFilters($query, $request);

        $customers = $this->paginateOrAll(
            $query->orderByDesc('customers.created_at'),
            $request
        );

        return [
            'status'  => true,
            'message' => trans('messages.success'),
            'data'    => CustomerResource::collection($customers),
        ];
    }

    /**
     * Get customers assigned to authenticated user.
     */
    public function getUserCustomer($request)
    {
        $query = $this->getUserCustomerQuery(auth()->user());

        $query = $this->applyCustomerFilters($query, $request);

        $customers = $this->paginateOrAll(
            $query->orderByDesc('customers.created_at'),
            $request
        );

        return [
            'status'  => true,
            'message' => trans('messages.success'),
            'data'    => CustomerResource::collection($customers),
        ];
    }

    /**
     * Get accounts and customers for selection/search.
     */
    public function FetchcustomersAccount($request)
    {
        $secondAccounts = Account::query()
            ->selectRaw(
                'accounts.id as id,
                accounts.name as account_name,
                NULL as customer_name,
                0 as customer_id'
            );

        $accounts = Account::query()
            ->selectRaw(
                'accounts.id as id,
                accounts.name as account_name,
                customers.name as customer_name,
                customers.id as customer_id'
            )
            ->join(
                'customers',
                'customers.account_id',
                '=',
                'accounts.id'
            );

        /*
         * Search.
         */
        if ($request->filled('search')) {
            $search = $request->search;

            $accounts->where(function ($query) use ($search) {
                $query
                    ->where('accounts.name', 'like', "%{$search}%")
                    ->orWhere('customers.name', 'like', "%{$search}%");
            });
        }

        /*
         * Return both accounts and customers.
         */
        $query = $accounts->union($secondAccounts);

        $results = $this->paginateOrAll(
            $query,
            $request
        );

        return [
            'status'  => true,
            'message' => trans('messages.success'),
            'data'    => AccountCustomerResource::collection($results),
        ];
    }

    /**
     * Create customer.
     */
    public function createCustomer($request)
    {
        try {
            $customer = DB::transaction(function () use ($request) {

                $validated = $request->validated();

                $validated['work_days'] = $this->normalizeWorkDays(
                    $request->work_days ?? []
                );

                return Customer::updateOrCreate(
                    [
                        'name' => $request->name,
                    ],
                    $validated
                );
            });

            return [
                'status'  => true,
                'message' => trans('messages.success'),
                'data'    => new CustomerResource($customer),
            ];
        } catch (\Throwable $e) {

            Log::error('Create customer failed', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);

            return [
                'status'  => false,
                'message' => trans('messages.server_error'),
            ];
        }
    }

    /**
     * Update customer.
     */
    public function updateCustomer($request, $customer)
    {
        if (!$customer) {
            return [
                'status'  => false,
                'message' => trans('messages.data_not_found'),
            ];
        }

        try {
            DB::transaction(function () use ($request, $customer) {

                $validated = $request->validated();

                $validated['work_days'] = $this->normalizeWorkDays(
                    $request->work_days ?? []
                );

                $customer->update($validated);
            });

            $customer->refresh();

            return [
                'status'  => true,
                'message' => trans('messages.success'),
                'data'    => new CustomerResource($customer),
            ];
        } catch (\Throwable $e) {

            Log::error('Update customer failed', [
                'customer_id' => $customer->id ?? null,
                'error'       => $e->getMessage(),
                'file'        => $e->getFile(),
                'line'        => $e->getLine(),
            ]);

            return [
                'status'  => false,
                'message' => trans('messages.server_error'),
            ];
        }
    }

    /**
     * Show customer.
     */
    public function show($customer)
    {
        if (!$customer) {
            return [
                'status'  => false,
                'message' => trans('messages.data_not_found'),
            ];
        }

        $customer->load([
            'account.brick',
            'accType',
            'specialty',
            'class',
        ]);

        return [
            'status'  => true,
            'message' => trans('messages.success'),
            'data'    => new CustomerDetailResource($customer),
        ];
    }

    /**
     * Delete customer.
     */
    public function deleteCustomer($customer)
    {
        if (!$customer) {
            return [
                'status'  => false,
                'message' => trans('messages.data_not_found'),
            ];
        }

        try {
            DB::transaction(function () use ($customer) {
                $customer->delete();
            });

            return [
                'status'  => true,
                'message' => trans('messages.success'),
            ];
        } catch (\Throwable $e) {

            Log::error('Delete customer failed', [
                'customer_id' => $customer->id ?? null,
                'error'       => $e->getMessage(),
                'file'        => $e->getFile(),
                'line'        => $e->getLine(),
            ]);

            return [
                'status'  => false,
                'message' => trans('messages.server_error'),
            ];
        }
    }

    /**
     * Base customers query.
     */
    protected function getCustomerQuery()
    {
        return Customer::query()
            ->select('customers.*')
            ->join(
                'accounts',
                'accounts.id',
                '=',
                'customers.account_id'
            )
            ->with([
                'account.brick',
                'accType',
                'specialty',
                'class',
            ]);
    }

    /**
     * Customers assigned to authenticated user.
     */
    protected function getUserCustomerQuery($user)
    {
        return $user->customers()
            ->select('customers.*')
            ->join(
                'accounts',
                'accounts.id',
                '=',
                'customers.account_id'
            )
            ->with([
                'account.brick',
                'accType',
                'specialty',
                'class',
            ])
            ->distinct('customers.id');
    }

    /**
     * Apply customer filters.
     *
     * account_id is handled explicitly because it is an important
     * relation/filter and should not depend on the generic filter scope.
     */
    protected function applyCustomerFilters($query, $request)
    {
        /*
         * Generic filters.
         *
         * Remove account_id from the request before calling filter()
         * so it does not get handled incorrectly by the generic scope.
         */
        $filterRequest = clone $request;

        $accountId = $request->input('account_id');

        $filterRequest->request->remove('account_id');

        $query->filter($filterRequest);

        /*
         * Account filter.
         */
        if (
            $accountId !== null &&
            $accountId !== '' &&
            is_numeric($accountId)
        ) {
            $query->where(
                'customers.account_id',
                (int) $accountId
            );
        }

        return $query;
    }

    /**
     * Normalize customer work days.
     */
    protected function normalizeWorkDays($workDays): array
    {
        if (!is_array($workDays) || empty($workDays)) {
            return [];
        }

        return array_values(
            array_unique(
                array_map('intval', $workDays)
            )
        );
    }

    /**
     * Get doctor/customer statistics.
     */
    public function getDoctorCharts()
    {
        $accountData = AccType::query()
            ->orderBy('id')
            ->get();

        $specialtyData = Specialty::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ])
            ->toArray();

        /*
         * Get customer counts grouped by
         * account type + specialty.
         */
        $customers = Customer::query()
            ->select([
                'customers.acc_type_id',
                'customers.specialty_id',
                DB::raw('COUNT(customers.id) as count'),
            ])
            ->join(
                'acc_type',
                'acc_type.id',
                '=',
                'customers.acc_type_id'
            )
            ->join(
                'specialty',
                'specialty.id',
                '=',
                'customers.specialty_id'
            )
            ->groupBy(
                'customers.acc_type_id',
                'customers.specialty_id'
            )
            ->orderBy(
                'customers.acc_type_id',
                'asc'
            )
            ->get();

        /*
         * Convert to:
         *
         * account_type_id-specialty_id => count
         */
        $customerData = $customers->mapWithKeys(
            function ($customer) {
                $key = $customer->acc_type_id
                    . '-'
                    . $customer->specialty_id;

                return [
                    $key => (int) $customer->count,
                ];
            }
        );

        /*
         * Build final statistics.
         */
        $statisticsData = $accountData->map(
            function ($accountType) use (
                $specialtyData,
                $customerData
            ) {
                return [
                    'name' => $accountType->name,

                    'specialty_data' => array_map(
                        function ($specialty) use (
                            $customerData,
                            $accountType
                        ) {
                            $key = $accountType->id
                                . '-'
                                . $specialty['id'];

                            return [
                                'id'    => $specialty['id'],
                                'name'  => $specialty['name'],
                                'count' => $customerData->get($key, 0),
                            ];
                        },
                        $specialtyData
                    ),
                ];
            }
        )->values();

        return [
            'status'  => true,
            'message' => trans('messages.success'),
            'data'    => $statisticsData,
        ];
    }

    public function getCustomersForManager($request,array $subordinateIds) {

    $clients = Customer::query()
        ->whereHas('users', function ($query) use ($subordinateIds) {
            $query->whereIn('users.id', $subordinateIds);
        })
        ->with([
            'account.brick',
            'accType',
            'specialty',
            'class',
        ]);

   
    if ($request->filled('account_id') && is_numeric($request->account_id)) {
        $clients->where('account_id',(int) $request->account_id);
    }

  
    $clients->filter($request);

    $clients = $this->paginateOrAll(
        $clients->orderByDesc('customers.created_at'),
        $request
    );

    return [
        'status'  => true,
        'message' => trans('messages.success'),
        'data'    => CustomerResource::collection($clients),
    ];
}

public function showCustomer($customerId,array $subordinateIds) {

    $customer = Customer::query()
        ->where('id', $customerId)
        ->whereHas('users', function ($query) use ($subordinateIds) {
            $query->whereIn('users.id', $subordinateIds);
        })
        ->with([
            'account',
            'specialty',
            'class',
        ])->first();

    if (!$customer) {
        return [
            'status'  => false,
            'message' => trans('messages.data_not_found'),
        ];
    }

    return [
        'status'  => true,
        'message' => trans('messages.success'),
        'data'    => new CustomerDetailResource($customer),
    ];
}

}
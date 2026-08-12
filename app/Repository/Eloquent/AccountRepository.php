<?php

namespace App\Repository\Eloquent;

use App\Http\Resources\API\AccountResource;
use App\Http\Traits\PaginatesResults;
use App\Models\AccType;
use App\Models\Account;
use App\Models\Classes;
use App\Repository\Interfaces\AccountInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountRepository implements AccountInterface
{
    use PaginatesResults;

    /**
     * Get all accounts.
     */
    public function getAll($request)
    {
        $accounts = $this->getAccountQuery();

        $accounts = $this->paginateOrAll(
            (clone $accounts)
                ->filter($request)
                ->orderByDesc('accounts.created_at'),
            $request
        );

        $data = AccountResource::collection($accounts);

        return [
            'status'  => true,
            'message' => trans('messages.success'),
            'data'    => $data,
        ];
    }

    /**
     * Get accounts assigned to authenticated user.
     */
    public function getUserAccount($request)
    {
        $accounts = $this->getUserAccountQuery(auth()->user());

        $accounts = $this->paginateOrAll(
            (clone $accounts)
                ->filter($request)
                ->orderByDesc('accounts.created_at'),
            $request
        );

        $data = AccountResource::collection($accounts);

        return [
            'status'  => true,
            'message' => trans('messages.success'),
            'data'    => $data,
        ];
    }

    /**
     * Create account.
     */
    public function createAccount($request)
    {
        try {
            $account = DB::transaction(function () use ($request) {
                $validated = $request->validated();

                return Account::updateOrCreate(
                    [
                        'name' => $request->name,
                    ],
                    $validated
                );
            });

            return [
                'status'  => true,
                'message' => trans('messages.success'),
                'data'    => new AccountResource($account),
            ];
        } catch (\Throwable $e) {
            Log::error('Create account failed', [
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
     * Update account.
     */
    public function updateAccount($request, $account)
    {
        if (!$account) {
            return [
                'status'  => false,
                'message' => trans('messages.data_not_found'),
            ];
        }

        try {
            DB::transaction(function () use ($request, $account) {
                $account->update($request->validated());
            });

            $account->refresh();

            return [
                'status'  => true,
                'message' => trans('messages.success'),
                'data'    => new AccountResource($account),
            ];
        } catch (\Throwable $e) {
            Log::error('Update account failed', [
                'account_id' => $account->id ?? null,
                'error'      => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
            ]);

            return [
                'status'  => false,
                'message' => trans('messages.server_error'),
            ];
        }
    }

    /**
     * Show account.
     */
    public function show($account)
    {
        if (!$account) {
            return [
                'status'  => false,
                'message' => trans('messages.data_not_found'),
            ];
        }

        $account->load([
            'accType',
            'brick',
            'class',
        ]);

        return [
            'status'  => true,
            'message' => trans('messages.success'),
            'data'    => new AccountResource($account),
        ];
    }

    /**
     * Delete account.
     */
    public function deleteAccount($account)
    {
        if (!$account) {
            return [
                'status'  => false,
                'message' => trans('messages.data_not_found'),
            ];
        }

        try {
            DB::transaction(function () use ($account) {
                $account->delete();
            });

            return [
                'status'  => true,
                'message' => trans('messages.success'),
            ];
        } catch (\Throwable $e) {
            Log::error('Delete account failed', [
                'account_id' => $account->id ?? null,
                'error'      => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
            ]);

            return [
                'status'  => false,
                'message' => trans('messages.server_error'),
            ];
        }
    }

    /**
     * Base account query.
     */
    protected function getAccountQuery()
    {
        return Account::query()
            ->select('accounts.*')
            ->join(
                'acc_type',
                'acc_type.id',
                '=',
                'accounts.acc_type_id'
            )
            ->with([
                'accType',
                'brick',
                'class',
            ]);
    }

    /**
     * Get accounts for authenticated user.
     */
    protected function getUserAccountQuery($user)
    {
        return $user->accounts()
            ->select('accounts.*')
            ->join(
                'acc_type',
                'acc_type.id',
                '=',
                'accounts.acc_type_id'
            )
            ->with([
                'accType',
                'brick',
                'class',
            ])
            ->groupBy('accounts.id');
    }

    /**
     * Get account charts and statistics.
     */
    public function getAccountCharts()
    {
        $isPharmacy = (int) request()->get('is_pharmacy', 0);

        $chartData = $this->drawAccountChart($isPharmacy);

        $statisticsData = $this->drawAccountStatistics($isPharmacy);

        return [
            'status'  => true,
            'message' => trans('messages.success'),
            'data'    => [
                'chart'          => $chartData,
                'staticticsData' => $statisticsData,
            ],
        ];
    }

    /**
     * Account chart grouped by account type.
     */
    protected function drawAccountChart($isPharmacy)
    {
        $accounts = Account::query()
            ->select([
                'acc_type.name as acc_name',
                'accounts.acc_type_id',
                DB::raw('COUNT(accounts.id) as count'),
            ])
            ->join(
                'acc_type',
                'acc_type.id',
                '=',
                'accounts.acc_type_id'
            )
            ->where('acc_type.is_pharmacy', $isPharmacy)
            ->groupBy(
                'accounts.acc_type_id',
                'acc_type.name'
            )
            ->orderBy(
                'accounts.acc_type_id',
                'asc'
            )
            ->get();

        return $accounts->map(function ($account) {
            return [
                'name'  => $account->acc_name,
                'count' => (int) $account->count,
            ];
        })->values();
    }

    /**
     * Account statistics grouped by account type and class.
     */
    protected function drawAccountStatistics($isPharmacy)
    {
        /*
         * Get all classes.
         */
        $classes = Classes::query()
            ->orderBy('name')
            ->get()
            ->toArray();

        /*
         * Get account types.
         */
        $accountTypes = AccType::query()
            ->where('is_pharmacy', $isPharmacy)
            ->orderBy('id')
            ->get();

        /*
         * Get account counts grouped by
         * account type and class.
         */
        $accounts = Account::query()
            ->select([
                'accounts.acc_type_id',
                'accounts.class_id',
                DB::raw('COUNT(accounts.id) as count'),
            ])
            ->join(
                'acc_type',
                'acc_type.id',
                '=',
                'accounts.acc_type_id'
            )
            ->join(
                'classes',
                'classes.id',
                '=',
                'accounts.class_id'
            )
            ->where('acc_type.is_pharmacy', $isPharmacy)
            ->groupBy(
                'accounts.acc_type_id',
                'accounts.class_id'
            )
            ->orderBy(
                'accounts.acc_type_id',
                'asc'
            )
            ->get();

        /*
         * Convert counts into:
         *
         * account_type_id-class_id => count
         */
        $accountData = $accounts->mapWithKeys(function ($account) {
            $key = $account->acc_type_id . '-' . $account->class_id;

            return [
                $key => (int) $account->count,
            ];
        });

        /*
         * Build statistics response.
         */
        return $accountTypes->map(function ($accountType) use (
            $classes,
            $accountData
        ) {
            return [
                'name' => $accountType->name,

                'classes_data' => array_map(
                    function ($class) use ($accountData, $accountType) {
                        $key = $accountType->id . '-' . $class['id'];

                        return [
                            'id'    => $class['id'],
                            'name'  => $class['name'],
                            'count' => $accountData->get($key, 0),
                        ];
                    },
                    $classes
                ),
            ];
        })->values();
    }
}
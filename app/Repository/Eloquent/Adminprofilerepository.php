<?php

namespace App\Repository\Eloquent;

use App\Enums\PositionKey;
use App\Models\User;
use App\Repository\Interfaces\AccountInterface;
use App\Repository\Interfaces\AdminProfileInterface;
use App\Http\Traits\PaginatesResults;
use Illuminate\Http\Request;

/**
 * Powers the Admin-panel "view profile by id" screens for the three
 * hierarchy roles (Manager -> Supervisor -> Sales Rep).
 *
 * Unlike SupervisorRepository / SalesRepRepository (used by the Manager
 * panel), these methods do NOT check that $request->user() is the target's
 * manager/supervisor — an admin can look up anyone. Ownership is only
 * checked where it protects data integrity (e.g. the given id must
 * actually hold the expected position), never against $request->user().
 */
class AdminProfileRepository implements AdminProfileInterface
{
    use PaginatesResults;

    protected AccountInterface $accounts;

    public function __construct(AccountInterface $accounts)
    {
        $this->accounts = $accounts;
    }

    // ---------------------------------------------------------------
    // Manager
    // ---------------------------------------------------------------

    public function managerProfile($id)
    {
        $manager = $this->findByPosition($id, PositionKey::MANAGER->value);

        if (!$manager) {
            return $this->notFound();
        }

        $manager->load([
            'userposition',
            'branches:id,name',
            'branchDepartments.branch:id,name',
            'branchDepartments.department:id,name',
        ]);

        return [
            'status'  => true,
            'message' => trans('messages.success'),
            'data'    => ['manager' => $manager],
        ];
    }

    public function managerSupervisors(Request $request, $id)
    {
        $manager = $this->findByPosition($id, PositionKey::MANAGER->value);

        if (!$manager) {
            return $this->notFound();
        }

        $query = User::with([
                'userposition',
                'branches:id,name',
                'branchDepartments.branch:id,name',
                'branchDepartments.department:id,name',
            ])
            ->where('manager_id', $manager->id)
            ->whereHas('userposition', fn ($q) =>
                $q->where('ps_key', PositionKey::SUPERVISOR->value)
            )
            ->when($request->filled('search'), fn ($q) =>
                $q->where('name', 'like', '%' . $request->search . '%')
            )
            ->filter($request)
            ->latest();

        $supervisors = $this->paginateOrAll($query, $request);

        return [
            'status'  => true,
            'message' => trans('messages.success'),
            'data'    => $supervisors,
        ];
    }

    public function managerAccounts(Request $request, $id)
    {
        $manager = $this->findByPosition($id, PositionKey::MANAGER->value);

        if (!$manager) {
            return $this->notFound();
        }

        // Whole team's accounts: every subordinate under this manager,
        // supervisors and reps alike.
        return $this->accounts->getAccountsForManager($request, $manager->getAllSubordinateIds());
    }

    public function managerCustomers(Request $request, $id)
    {
        $manager = $this->findByPosition($id, PositionKey::MANAGER->value);

        if (!$manager) {
            return $this->notFound();
        }

        // ASSUMPTION: a CustomerRepository::getCustomersForManager() method
        // mirroring AccountRepository::getAccountsForManager() — same
        // whereHas('users', whereIn subordinateIds) pattern against the
        // Customer model. Swap this for the real service once it exists.
        return app(\App\Repository\Interfaces\CustomerInterface::class)
            ->getCustomersForManager($request, $manager->getAllSubordinateIds());
    }

    // ---------------------------------------------------------------
    // Supervisor
    // ---------------------------------------------------------------

    public function supervisorProfile($id)
    {
        $supervisor = $this->findByPosition($id, PositionKey::SUPERVISOR->value);

        if (!$supervisor) {
            return $this->notFound();
        }

        $supervisor->load([
            'userposition',
            'branches:id,name',
            'branchDepartments.branch:id,name',
            'branchDepartments.department:id,name',
            'manager:id,name', // who this supervisor reports to
        ]);

        return [
            'status'  => true,
            'message' => trans('messages.success'),
            'data'    => [
                'supervisor' => $supervisor,
                'manager'    => $supervisor->manager,
            ],
        ];
    }

    public function supervisorSalesReps(Request $request, $id)
    {
        $supervisor = $this->findByPosition($id, PositionKey::SUPERVISOR->value);

        if (!$supervisor) {
            return $this->notFound();
        }

        $query = User::with([
                'userposition',
                'branches:id,name',
                'branchDepartments.branch:id,name',
                'branchDepartments.department:id,name',
            ])
            ->whereIn('id', $supervisor->getAllSubordinateIds())
            ->whereHas('userposition', fn ($q) =>
                $q->where('ps_key', PositionKey::SALES_REP->value)
            )
            ->when($request->filled('search'), fn ($q) =>
                $q->where('name', 'like', '%' . $request->search . '%')
            )
            ->filter($request)
            ->latest();

        $reps = $this->paginateOrAll($query, $request);

        return [
            'status'  => true,
            'message' => trans('messages.success'),
            'data'    => $reps,
        ];
    }

    public function supervisorAccounts(Request $request, $id)
    {
        $supervisor = $this->findByPosition($id, PositionKey::SUPERVISOR->value);

        if (!$supervisor) {
            return $this->notFound();
        }

        return $this->accounts->getAccountsForManager($request, $supervisor->getAllSubordinateIds());
    }

    public function supervisorCustomers(Request $request, $id)
    {
        $supervisor = $this->findByPosition($id, PositionKey::SUPERVISOR->value);

        if (!$supervisor) {
            return $this->notFound();
        }

        return app(\App\Repository\Interfaces\CustomerInterface::class)
            ->getCustomersForManager($request, $supervisor->getAllSubordinateIds());
    }

    // ---------------------------------------------------------------
    // Sales Rep
    // ---------------------------------------------------------------

    public function salesRepProfile($id)
    {
        $salesRep = $this->findByPosition($id, PositionKey::SALES_REP->value);

        if (!$salesRep) {
            return $this->notFound();
        }

        $salesRep->load([
            'userposition',
            'branches:id,name',
            'branchDepartments.branch:id,name',
            'branchDepartments.department:id,name',
            'manager:id,name', // the supervisor this rep reports to
        ]);

        return [
            'status'  => true,
            'message' => trans('messages.success'),
            'data'    => [
                'sales_rep' => $salesRep,
                'manager'   => $salesRep->manager,
            ],
        ];
    }

    public function salesRepAccounts(Request $request, $id)
    {
        $salesRep = $this->findByPosition($id, PositionKey::SALES_REP->value);

        if (!$salesRep) {
            return $this->notFound();
        }

        // A single rep's own assigned accounts — same helper, just scoped
        // to one id instead of a whole team.
        return $this->accounts->getAccountsForManager($request, [$salesRep->id]);
    }

    // ---------------------------------------------------------------
    // Shared helpers
    // ---------------------------------------------------------------

    protected function findByPosition($id, string $positionKey): ?User
    {
        return User::whereHas('userposition', fn ($q) => $q->where('ps_key', $positionKey))
            ->find($id);
    }

    protected function notFound(): array
    {
        return [
            'status'  => false,
            'message' => trans('messages.data_not_found'),
        ];
    }
}
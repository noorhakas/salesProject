<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\ObservantTrait;
use Carbon\Carbon;
use App\Repository\Interfaces\HasNotificationData;

class Visit extends Model implements HasNotificationData
{
	use SoftDeletes, ObservantTrait;
    protected $table = 'visits';
	protected $fillable = ['plan_id','user_id','account_id','customer_id','type','status','visit_date','start_time','end_time','confirmed_by' ,'notes','user_location_lat','user_location_lng','actual_start_date','actual_end_date','combine_with'];

	public function account()
    {
        return $this->belongsTo(Account::class);
    }

	public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

	public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

	public function user()
    {
        return $this->belongsTo(User::class);
    }

	public function visitdetails()
    {
        return $this->hasMany(VisitDetails::class);
    }

     public function doubleVisit()
    {
        return $this->belongsTo(User::class,'combine_with','id');
    }
	
    // public function getStatusAttribute($value)
    // {
    //     return (Carbon::parse($this->visit_date)->toDateString() < Carbon::now()->toDateString()) && $value != 2 ? 5 : $value;
    // }

   public function scopeFilter($q, $request)
{
    $status = $request->filled('status')
        ? (int) $request->status
        : null;

    return $q
        /*
        |--------------------------------------------------------------------------
        | Plan
        |--------------------------------------------------------------------------
        */
        ->when(
            $request->filled('plan_id'),
            fn ($q) =>
                $q->where('visits.plan_id', $request->plan_id)
        )

        /*
        |--------------------------------------------------------------------------
        | Status / Type
        |--------------------------------------------------------------------------
        */
        ->when($status !== null, function ($q) use ($status) {

            if ($status === -1) {

                // Planned
                $q->where('visits.type', 0);

            } elseif ($status === -2) {

                // Unplanned
                $q->where('visits.type', 1);

            } else {

                $q->where('visits.status', $status);
            }
        })

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */
        ->when(
            $request->filled('search'),
            function ($q) use ($request) {

                $search = $request->input('search');

                $q->where(function ($query) use ($search) {

                    $query
                        ->where('customers.name', 'like', "%{$search}%")
                        ->orWhere('accounts.name', 'like', "%{$search}%");
                });
            }
        )

        /*
        |--------------------------------------------------------------------------
        | Start Date
        |--------------------------------------------------------------------------
        */
        ->when(
            $request->filled('start_date'),
            function ($q) use ($request) {

                $startDate = $request->input('start_date');

                $q->where(function ($query) use ($startDate) {

                    $query
                        ->whereDate(
                            'visits.visit_date',
                            '>=',
                            $startDate
                        )
                        ->orWhereDate(
                            'visits.actual_start_date',
                            '>=',
                            $startDate
                        );
                });
            }
        )

        /*
        |--------------------------------------------------------------------------
        | End Date
        |--------------------------------------------------------------------------
        */
        ->when(
            $request->filled('end_date'),
            function ($q) use ($request) {

                $endDate = $request->input('end_date');

                $q->where(function ($query) use ($endDate) {

                    $query
                        ->whereDate(
                            'visits.visit_date',
                            '<=',
                            $endDate
                        )
                        ->orWhereDate(
                            'visits.actual_start_date',
                            '<=',
                            $endDate
                        );
                });
            }
        )

        /*
        |--------------------------------------------------------------------------
        | Exact Visit Date
        |--------------------------------------------------------------------------
        */
        ->when(
            $request->filled('visit_date'),
            fn ($q) =>
                $q->whereDate(
                    'visits.visit_date',
                    $request->input('visit_date')
                )
        )

        /*
        |--------------------------------------------------------------------------
        | User
        |--------------------------------------------------------------------------
        */
        ->when(
            $request->filled('user_id'),
            fn ($q) =>
                $q->where(
                    'visits.user_id',
                    $request->input('user_id')
                )
        )

        /*
        |--------------------------------------------------------------------------
        | Customer
        |--------------------------------------------------------------------------
        */
        ->when(
            $request->filled('customer_id'),
            fn ($q) =>
                $q->where(
                    'visits.customer_id',
                    $request->input('customer_id')
                )
        );
}

    public function getNotificationData(): array
    {
        return [
            'type'          => 'visit',
            'id'            => $this->id,
            'plan_id'       => $this->plan_id,
            'user_name'     => $this->user?->name,
            'account_name'  => $this->account?->name,
            'customer_name' => $this->customer?->name,
            'visit_date'    => $this->visit_date,
            'start_time'    => $this->start_time,
            'end_time'      => $this->end_time,
            'status'        => $this->status,
        ];
    }

}
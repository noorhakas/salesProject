<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\PlanStatusEnum;
use App\Enums\VisitStatusEnum;
use App\Http\Traits\ObservantTrait;
use Carbon\Carbon;

class Plan extends Model
{
    use SoftDeletes, ObservantTrait;

    protected $table = 'plans';
    protected $fillable = ['Uuid', 'user_id', 'type', 'start_date', 'end_date', 'status', 'approved_or_rejected_by'];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    /**
     * Only total_days is auto-appended (it's a pure date calculation,
     * no query). total_visits is NOT appended on purpose — it runs a
     * query against visits(), so it should only be pulled when actually
     * needed (e.g. explicitly in PlansResource), not on every place this
     * model gets serialized, to avoid an N+1 query when listing plans.
     */
    protected $appends = ['total_days'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->Uuid = self::generateNumber();
        });
    }

    public static function generateNumber()
    {
        $number = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

        if (self::where('Uuid', $number)->count()) {
            $number = self::generateNumber();
        }

        return $number;
    }

    /**
     * Total number of days the plan spans, counting both start_date and
     * end_date (e.g. Mon -> Mon = 1 day, Mon -> Tue = 2 days).
     * +1 over diffInDays() because diffInDays() only counts the gap
     * between the two dates, not the dates themselves.
     */
    public function getTotalDaysAttribute(): int
    {
        $startDate = Carbon::parse($this->start_date);
        $endDate = Carbon::parse($this->end_date);

        return $startDate->diffInDays($endDate) + 1;
    }

    /**
     * Count of this plan's visits that were actually completed
     * (VisitStatusEnum::Visited). Triggers a query — see the note on
     * $appends above before adding this to $appends.
     */
    public function getTotalVisitsAttribute(): int
    {
        return (int) $this->visits()
            ->where('status', (VisitStatusEnum::Visited)['id'])
            ->count();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'approved_or_rejected_by', 'id');
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function plan_status()
    {
        return $this->hasMany(PlanStatus::class);
    }

    /**
     * Filters plans by search term / date range / owner / status.
     *
     * The `status` param accepts both real DB statuses (Pending, Accepted,
     * Rejected) and the computed, date-derived ones used by PlansResource
     * (Completed, Upcoming, In Progress) — see PlanStatusEnum for the
     * full mapping. Keeping both sides on the same enum constants means
     * a filter value here always means the same thing as the badge the
     * user sees on a plan in PlansResource.
     */
    public function scopeFilter($q, $request)
    {
        // status=0 ("Pending") is also PHP's falsy default, so an explicit
        // "-1" sentinel is used to distinguish "filter by Pending" from
        // "no status filter was sent".
        $status = isset($request->status) && $request->status == "0" ? "-1" : $request->status;

        $q = $q
            ->when($request->search, fn ($q, $v) => $q->where('Uuid', 'like', "%{$v}%"))
            ->when($request->date, fn ($q, $v) => $q->whereDate('plans.end_date', '<=', $v))
            ->when($request->start_date, fn ($q, $v) => $q->whereDate('plans.start_date', '>=', $v))
            ->when($request->end_date, fn ($q, $v) => $q->whereDate('plans.end_date', '<=', $v))
            ->when($request->user_id, fn ($q, $v) => $q->where('plans.user_id', $v))
            ->when($status, function ($q) use ($status) {
                switch ((int) $status) {
                    case PlanStatusEnum::Completed:
                        // Explicitly marked completed, OR its window has
                        // simply passed regardless of stored status.
                        $q->where(function ($q) {
                            $q->where('plans.status', PlanStatusEnum::Completed)
                              ->orWhereDate('plans.end_date', '<', Carbon::now()->toDateString());
                        });
                        break;

                    case PlanStatusEnum::Upcoming:
                        $q->whereDate('plans.start_date', '>', Carbon::now()->toDateString());
                        break;

                    case PlanStatusEnum::Accepted:
                        $q->where('plans.status', PlanStatusEnum::Accepted)
                          ->whereDate('plans.end_date', '>=', Carbon::now()->toDateString());
                        break;

                    case PlanStatusEnum::InProgress:
                        $q->where('plans.status', PlanStatusEnum::Accepted)
                          ->whereDate('plans.start_date', '<=', Carbon::now()->toDateString())
                          ->whereDate('plans.end_date', '>=', Carbon::now()->toDateString());
                        break;

                    default:
                        $q->where('plans.status', $status);
                        break;
                }
            });

        return $q;
    }
}
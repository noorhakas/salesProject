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

    public function getTotalDaysAttribute(): int
    {
        $startDate = Carbon::parse($this->start_date);
        $endDate = Carbon::parse($this->end_date);

        return $startDate->diffInDays($endDate) + 1;
    }

   
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

   
    public function scopeFilter($q, $request)
{
    $q = $q
        ->when($request->search, fn ($q, $v) => $q->where('Uuid', 'like', "%{$v}%"))
        ->when($request->date, fn ($q, $v) => $q->whereDate('plans.end_date', '<=', $v))
        ->when($request->start_date, fn ($q, $v) => $q->whereDate('plans.start_date', '>=', $v))
        ->when($request->end_date, fn ($q, $v) => $q->whereDate('plans.end_date', '<=', $v))
        ->when($request->user_id, fn ($q, $v) => $q->where('plans.user_id', $v))
        ->when(
            isset($request->status) && $request->status !== '',
            function ($q) use ($request) {
                $status = (int) $request->status;

                switch ($status) {
                    case PlanStatusEnum::Completed:
                       
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
            }
        );

    return $q;
}
}
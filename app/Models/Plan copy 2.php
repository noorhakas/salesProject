<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\PlanStatusEnum;
use App\Enums\VisitStatusEnum;
use App\Http\Traits\ObservantTrait;
use Carbon\Carbon;
use App\Repository\Interfaces\HasNotificationData;


class Plan extends Model implements HasNotificationData
{
    use SoftDeletes, ObservantTrait;

    protected $table = 'plans';
    protected $fillable = ['Uuid', 'user_id', 'type', 'start_date', 'end_date', 'status', 'approved_or_rejected_by'];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];


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

    public function getDisplayStatusAttribute(): int
    {
        return $this->resolveDisplayStatus()[0];
    }

    public function getDisplayStatusAsStringAttribute(): string
    {
        return $this->resolveDisplayStatus()[1];
    }

    protected function resolveDisplayStatus(): array
    {
        $today = Carbon::now()->toDateString();
        $startDate = Carbon::parse($this->start_date)->toDateString();
        $endDate = Carbon::parse($this->end_date)->toDateString();

        if ((int) $this->status === PlanStatusEnum::Accepted) {
            if ($startDate <= $today && $endDate >= $today) {
                return [PlanStatusEnum::InProgress, PlanStatusEnum::toString(PlanStatusEnum::InProgress)];
            }

            if ($endDate < $today) {
                return [PlanStatusEnum::Completed, PlanStatusEnum::toString(PlanStatusEnum::Completed)];
            }

            if ($startDate > $today) {
                return [PlanStatusEnum::Upcoming, PlanStatusEnum::toString(PlanStatusEnum::Upcoming)];
            }
        }

        if ((int) $this->status === PlanStatusEnum::Pending && $endDate < $today) {
            return [PlanStatusEnum::Expired, PlanStatusEnum::toString(PlanStatusEnum::Expired)];
        }

        return [$this->status, PlanStatusEnum::toString($this->status)];
    }

    public function getTotalDaysAttribute(): int
    {
        $startDate = Carbon::parse($this->start_date);
        $endDate = Carbon::parse($this->end_date);

        return $startDate->diffInDays($endDate) + 1;
    }


    public function getTotalVisitsAttribute(): int
    {
        return (int) $this->visits()->count();
    }

    public function getTotalVisitedAttribute(): int
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

    public function scopeFilter($q, $request, bool $applyStatus = true)
{
    $q = $q
        ->when($request->search, fn ($q, $v) => $q->where('Uuid', 'like', "%{$v}%"))
        ->when($request->user_id, fn ($q, $v) => $q->where('plans.user_id', $v))
        ->when(
            $request->filled('start_date') || $request->filled('end_date'),
            function ($q) use ($request) {
                if ($request->filled('start_date')) {
                    $q->whereDate('plans.end_date', '>=', $request->start_date);
                }
                if ($request->filled('end_date')) {
                    $q->whereDate('plans.start_date', '<=', $request->end_date);
                }
            }
        );

    if ($applyStatus) {
        $q->when(
            $request->filled('status'),
            fn ($q) => static::applyStatusFilter($q, (int) $request->status, $request)
        );
    }

    return $q;
}
   
    public static function applyStatusFilter($q, int $status, $request = null)
{
    $referenceDate = $request?->filled('start_date')
        ? Carbon::parse($request->start_date)->toDateString()
        : Carbon::now()->toDateString();

    switch ($status) {
        case PlanStatusEnum::Completed:

            $q->where('plans.status', PlanStatusEnum::Accepted)
                ->whereDate('plans.end_date', '<', $referenceDate);

            break;

        case PlanStatusEnum::Upcoming:

            $q->where('plans.status', PlanStatusEnum::Accepted)
                ->whereDate('plans.start_date', '>', $referenceDate);

            break;

        case PlanStatusEnum::Accepted:

            $q->where('plans.status', PlanStatusEnum::Accepted)
                ->whereDate('plans.start_date', '<=', $referenceDate)
                ->whereDate('plans.end_date', '>=', $referenceDate);

            break;

        case PlanStatusEnum::InProgress:

            $q->where('plans.status', PlanStatusEnum::Accepted)
                ->whereDate('plans.start_date', '<=', $referenceDate)
                ->whereDate('plans.end_date', '>=', $referenceDate);

            break;

        case PlanStatusEnum::Pending:

            $q->where('plans.status', PlanStatusEnum::Pending)
                ->whereDate('plans.end_date', '>=', $referenceDate);

            break;

        case PlanStatusEnum::Expired:

            $q->where('plans.status', PlanStatusEnum::Pending)
                ->whereDate('plans.end_date', '<', $referenceDate);

            break;

        default:

            $q->where('plans.status', $status);

            break;
    }

    return $q;
}

    public function getNotificationData(): array
    {
        return [
            'type'            => 'plan',
            'id'              => $this->id,
            'Uuid'            => $this->Uuid,
            'plan_type'       => $this->type,
            'user_name'       => $this->user?->name,
            'manager_name'    => $this->manager?->name,
            'date_from'       => $this->start_date?->format('Y-m-d'),
            'date_to'         => $this->end_date?->format('Y-m-d'),
            'total_days'      => $this->total_days,
            'status'          => $this->display_status,
            'statusAsString'  => $this->display_status_as_string,
        ];
    }
}
<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Http\Traits\ObservantTrait;
use Carbon\Carbon;


class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasRoles, ObservantTrait;
	use SoftDeletes;

//	protected function getDefaultGuardName(): string { return 'web'; }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name','email','password','user_name','status','position','access_all_data','DeviceToken','DeviceType','active_code'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

	 public function getRoleId()
    {
       return isset($this->roles) && $this->roles->isNotEmpty()?$this->roles[0]->id:0;
    }

    public function getRoleName()
    {
       return isset($this->roles) && $this->roles->isNotEmpty()?$this->roles[0]->name:'';
    }

	protected function SetPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

	public function bricks()
    {
        return $this->belongsToMany(Bricks::class, 'user_bricks','user_id','brick_id');
    }

	public function products()
    {
        return $this->belongsToMany(Product::class, 'user_products');
    }

	public function customers()
    {
        return $this->belongsToMany(Customer::class, 'user_customers');
    }

	public function plans()
    {
        return $this->hasMany(Plan::class);
    }

	public function visits()
    {
        return $this->hasMany(Visit::class)->join('customers', 'customers.id', '=', 'visits.customer_id');
    }

	public function userVisits()
    {
        return $this->belongsToMany(Customer::class, 'visits','user_id','customer_id');
    }

	public function logs(): MorphMany
    {
        return $this->morphMany(SiteLog::class, 'loggable');
    }

	public static function getCurrentPlan(){
        return auth()->user()->plans()->whereDate('plans.end_date','>=' ,Carbon::today())->orderBy('plans.created_at','DESC')->first();
	}

	public function scopeFilter($q,$request)
    {
		$q =$q->when($request->position_id,fn($q, $v) =>
		        $q->where('position', $v))
		      ->when($request->search,fn($q, $v) => 
					$q->where('name', 'like', "%{$v}%")
					->orWhere('user_name', 'like', "%{$v}%")
					->orWhere('email', 'like', "%{$v}%"));

        return $q;
    }



}

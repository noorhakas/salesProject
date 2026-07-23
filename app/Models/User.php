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
    protected $fillable = ['name','email','password','user_name','whatsapp','phone','status','position','access_all_data','DeviceToken','DeviceType','active_code','manager_id','is_admin'];

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

     public function userposition()
    {
        return $this->belongsTo(Position::class,'position','id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    #employee related to this manager
    public function subordinates()
    {
        return $this->hasMany(User::class, 'manager_id');
    }

	#all user ids beneath this manager at every level (recursive subtree, excludes self)
	public function getAllSubordinateIds(): array
	{
		$collected = [];
		$queue = [$this->id];

		while (!empty($queue)) {
			$children = User::whereIn('manager_id', $queue)
				->pluck('id')
				->all();

			$children = array_values(array_diff($children, $collected, [$this->id]));

			if (empty($children)) {
				break;
			}

			$collected = array_merge($collected, $children);
			$queue = $children;
		}

		return array_values(array_unique($collected));
	}

	public function bricks()
    {
        return $this->belongsToMany(Bricks::class, 'user_bricks','user_id','brick_id');
    }

   public function departments()
    {
        return $this->belongsToMany( Department::class, 'user_departments','user_id','department_id');
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class,'user_branches','user_id','branch_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'user_products','user_id','product_id');
    }
	public function customers()
    {
        return $this->belongsToMany(Customer::class, 'user_customers','user_id','customer_id');
    }

	public function accounts()
    {
        return $this->belongsToMany(Account::class, 'user_customers','user_id','account_id');
    }

	public function plans()
    {
        return $this->hasMany(Plan::class);
    }

	public function visits()
    {
        return $this->hasMany(Visit::class);
    }

	public function userVisits()
    {
        return $this->belongsToMany(Customer::class, 'visits','user_id','customer_id');
    }

	public function logs(): MorphMany
    {
        return $this->morphMany(SiteLog::class, 'loggable');
    }

    public function attendance()
    {
        return $this->hasOne(Attendance::class)
            ->latestOfMany();
    }

	public static function getCurrentPlan(){
        return auth()->user()->plans()->Where('status',1)->whereDate('plans.start_date','<=' ,Carbon::today())->whereDate('plans.end_date','>=' ,Carbon::today())->orderBy('plans.created_at','ASC')->first();
	}


	public function scopeFilter($q,$request)
    { 
		$q =$q->when($request->position_id,fn($q, $v) =>
		       	$q->where('position', $v)) 
		          ->when($request->search,fn($q, $v) => 
                $q->where(function ($query) use ($v) {
                    $query->orWhere('name', 'like', "%{$v}%")
                        ->orWhere('email', 'like', "%{$v}%")
                        ->orWhere('user_name', 'like', "%{$v}%");
                }));

        return $q;
    }

}

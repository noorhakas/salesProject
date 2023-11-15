<?php

namespace App\Models;

use Throwable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use PhpParser\Node\Stmt\TryCatch;

class SiteLog extends Model
{
    use HasFactory;

    protected $casts = [
        'old_value' =>  'json',
        'new_value' =>  'json',
        'header'    =>  'json',
        'request'   =>  'json',
    ];

    protected $fillable = [
        'action',
        'ip',
        'url',
        'code',
        'method',
        'message',
        'trace',
        'old_value',
        'new_value',
        'action_onable_type',
        'action_onable_id',
        'request',
        'header'
    ];

    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

	public function user(){
		 return $this->belongsTo(User::class,'loggable_id','id');
	}

    public function getUser()
    {
        return auth()->user();
    }


    public function reportDatabase($database, $action)
    {
        $siteLog = new SiteLog($this->returnCreateArrayDatabase($database, $action));
        $user = $this->getUser();
        if ($user) {
            return $user->logs()->save($siteLog);
        } else {
            return $siteLog->save();
        }
    }

    public function returnCreateArrayDatabase($database, $action)
    {
        return [
            'action'                => $action,
            'code'                  => 0,
            'message'               => 0,
            'trace'                 => null,
            'action_onable_type'    => $database['get_class'],
            'action_onable_id'      => $database['id'],
            'old_value'             => json_encode($database['old_value']),
            'new_value'             => json_encode($database['new_value']),
            'method'                => request()->getMethod(),
            'ip'                    => request()->ip(),
            'url'                   => request()->fullUrl(),
            'request'               => json_encode([
                'request' => request()->request->all(),
                'attributes' => request()->attributes->all(),
                'files' => request()->files->all()
            ]),
            'header'                => json_encode(request()->headers->all()),
        ];
    }


    public function reportResponse($response)
    {
        $siteLog = new SiteLog($this->returnCreateArrayResponse($response));
        $user = $this->getUser();
        if ($user) {
            return $user->logs()->save($siteLog);
        } else {
            return $siteLog->save();
        }
    }

    public function returnCreateArrayResponse($response)
    {
        return [
            'action'  => 'Response',
            'code'    => trim($response->status()),
            'message' => trim($response->getContent()),
            'trace'   => null,
            'method'  => request()->getMethod(),
            'ip'      => request()->ip(),
            'url'     => request()->fullUrl(),
            'request' => json_encode([
                'request' => request()->request->all(),
                'attributes' => request()->attributes->all(),
                'files' => request()->files->all()
            ]),
            'header'  => json_encode(request()->headers->all()),
        ];
    }

    public function reportException(Throwable $exception)
    {
        $siteLog = new SiteLog($this->returnCreateArray($exception));
        $user = $this->getUser();
        if ($user) {
            return $user->logs()->save($siteLog);
        } else {
            return $siteLog->save();
        }
    }

    public function returnCreateArray(Throwable $exception)
    {
        try {
            $code = trim($exception->getStatusCode());
        } catch (\Throwable $th) {
            $code = trim($exception->getCode());
        }

        return [
            'action'  => 'Exception',
            'code'    => $code ,
            'message' => trim($exception->getMessage()),
            'trace'   => trim($exception->getTraceAsString()),
            'method'  => request()->getMethod(),
            'ip'      => request()->ip(),
            'url'     => request()->fullUrl(),
            'request' => json_encode([
                'request' => request()->request->all(),
                'attributes' => request()->attributes->all(),
                'files' => request()->files->all()
            ]),
            'header'  => json_encode(request()->headers->all()),
        ];
    }
}

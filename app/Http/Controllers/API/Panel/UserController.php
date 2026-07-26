<?php

namespace App\Http\Controllers\API\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ProfileRequest;
use App\Http\Resources\API\PlansResource;
use App\Http\Resources\API\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{

    public function myProfile(Request $request)
    {
        return $this->response_api(
            true,
            trans('messages.success'),
            new UserResource($request->user())
        );
    }

    public function updateProfile(ProfileRequest $request)
    {
        try {

            $user = auth()->user();

            $user->update($request->validated());

            return $this->response_api(
                true,
                trans('messages.success'),
                new UserResource($user)
            );

        } catch (\Exception $e) {

            Log::error('Profile Update Error', [
                'user_id' => auth()->id(),
                'message' => $e->getMessage()
            ]);

            return $this->response_api(
                false,
                trans('messages.server_error')
            );
        }
    }

    public function myCurrentPlan()
    {
        $currentPlan = User::getCurrentPlan();

        return $this->response_api(
            true,
            trans('messages.success'),
            $currentPlan
                ? new PlansResource($currentPlan)
                : (object)[]
        );
    }

    public function getPositionList()
    {
        return $this->response_api(
            true,
            trans('messages.success'),
            \App\Enums\UserPositionEnum::toArray()
        );
    }

   
}
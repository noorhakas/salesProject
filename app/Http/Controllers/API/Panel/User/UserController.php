<?php

namespace App\Http\Controllers\API\Panel\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
   public function index(Request $request)
    {
        $currentUser = auth()->user();

        $query = User::where('id', '!=', $currentUser->id)->where('id', '!=', 1)
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->where('name', 'like', "%{$request->search}%")
                        ->orWhere('user_name', 'like', "%{$request->search}%");
                });
            });

        if ($request->filled('per_page') && (int) $request->per_page === -1) {
            $limit = (clone $query)->count();
            $limit = $limit > 0 ? $limit : 1; // عشان paginate ميرفضش limit = 0
        } else {
            $limit = $request->filled('per_page') && is_numeric($request->per_page)
                ? $request->per_page
                : 20;
        }

        $colleagues = $query->paginate($limit);

        return $this->response_api(
            true,
            trans('messages.success'),
            UserResource::collection($colleagues)
        );
    }
}
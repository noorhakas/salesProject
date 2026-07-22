<?php

namespace App\Http\Controllers\API\Panel\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\CoachResource;
use App\Models\User;
use App\Enums\PositionKey;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = auth()->user();

        $allowedPositions = [
            PositionKey::SUPERVISOR->value,
            PositionKey::AREA_MANAGER->value,
        ];

        $query = User::where('id', '!=', $currentUser->id)
            ->where('id', '!=', 1)
            ->whereHas('userposition', function ($q) use ($allowedPositions) {
                $q->whereIn('ps_key', $allowedPositions);
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->where('name', 'like', "%{$request->search}%")
                        ->orWhere('user_name', 'like', "%{$request->search}%");
                });
            })
            ->with('userposition');

        if ($request->filled('per_page') && (int) $request->per_page === -1) {
            $limit = (clone $query)->count();
            $limit = $limit > 0 ? $limit : 1;
        } else {
            $limit = $request->filled('per_page') && is_numeric($request->per_page)
                ? $request->per_page
                : 20;
        }

        $coach = $query->paginate($limit);

        return $this->response_api(
            true,
            trans('messages.success'),
            CoachResource::collection($coach)
        );
    }
}
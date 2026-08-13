<?php

namespace App\Repository\Eloquent;

use App\Http\Resources\API\BricksResource;
use App\Http\Traits\PaginatesResults;
use App\Models\Bricks;
use App\Repository\Interfaces\BrickInterface;
use Illuminate\Support\Facades\DB;

class BrickRepository implements BrickInterface
{
    use PaginatesResults;

    public function getAll($request)
    {
        $query = Bricks::query()
            ->with('branch')
            ->filter($request)
            ->orderByDesc('bricks.created_at');

        $bricks = $this->paginateOrAll($query, $request);

        return $this->success(
            BricksResource::collection($bricks)
        );
    }

    public function createBrick($request)
    {
        try {
            $brick = DB::transaction(function () use ($request) {
                return Bricks::updateOrCreate(
                    ['name' => $request->name],
                    $request->validated()
                );
            });

            $brick->load('branch');

            return $this->success(new BricksResource($brick));

        } catch (\Throwable $e) {
            return $this->failure('server_error');
        }
    }

    public function updateBrick($request, $id)
    {
        $brick = Bricks::find($id);

        if (!$brick) {
            return $this->failure('data_not_found');
        }

        try {
            $brick->update($request->validated());
            $brick->refresh();
            $brick->load('branch');

            return $this->success(new BricksResource($brick));

        } catch (\Throwable $e) {
            return $this->failure('server_error');
        }
    }

    public function show($id)
    {
        $brick = Bricks::with('branch')->find($id);

        if (!$brick) {
            return $this->failure('data_not_found');
        }

        return $this->success(new BricksResource($brick));
    }

    public function deleteBrick($id)
    {
        $brick = Bricks::find($id);

        if (!$brick) {
            return $this->failure('data_not_found');
        }

        try {
            $brick->delete();
            return $this->success(null);

        } catch (\Throwable $e) {
            return $this->failure('server_error');
        }
    }
}
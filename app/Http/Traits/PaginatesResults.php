<?php

namespace App\Http\Traits;

trait PaginatesResults
{
    protected const DEFAULT_PER_PAGE = 20;
    protected const ALL_RESULTS = -1;

    protected function paginateOrAll($query, $request, int $default = self::DEFAULT_PER_PAGE)
    {
        $requested = $request->input('per_page');

        if (is_numeric($requested) && (int) $requested === self::ALL_RESULTS) {
            $total = (clone $query)->toBase()->getCountForPagination();

            return $query->paginate(max($total, 1));
        }

        $perPage = is_numeric($requested)
            ? (int) $requested
            : $default;

        return $query->paginate(
            $perPage > 0 ? $perPage : self::DEFAULT_PER_PAGE
        );
    }

    protected function success($data): array
    {
        return ['status' => true, 'message' => trans('messages.success'), 'data' => $data];
    }

    protected function failure(string $messageKey): array
    {
        return ['status' => false, 'message' => trans("messages.{$messageKey}")];
    }
}

?>
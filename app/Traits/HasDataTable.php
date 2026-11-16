<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

trait HasDataTable
{
    protected function processDataTable(
        $query,
        $request,
        $searchable = [],
        $filterable = [],
        $cacheKey = null,
        $cacheTtl = 60,
        $defaultSortBy = 'created_at',
        $defaultSortDirection = 'desc'
    ) {
        // Apply search with performance considerations
        if ($request->search && ! empty($searchable)) {
            $this->applySearch($query, $request->search, $searchable);
        }

        // Apply filters with indexing in mind
        foreach ($filterable as $filter => $callback) {
            if ($request->has($filter) && ! empty($request->$filter)) {
                $this->applyFilter($query, $filter, $request->$filter, $callback);
            }
        }

        // Optimized sorting
        $sortBy = $request->input('sort_by', $defaultSortBy);
        $sortDirection = $request->input('sort_direction', $defaultSortDirection);

        if ($sortBy) {
            $this->applySorting($query, $sortBy, $sortDirection);
        }

        // Caching mechanism for paginated results
        if ($cacheKey) {
            $page = $request->page ?? 1;
            $cacheKey = "datatable:{$cacheKey}:page_{$page}:".md5(json_encode($request->all()));

            return Cache::remember($cacheKey, $cacheTtl, function () use ($query, $request) {
                return $this->executePagination($query, $request);
            });
        }

        return $this->executePagination($query, $request);
    }

    /**
     * Limit sorting to known columns. The DataTable lets users click any column
     * header (including computed ones), so anything else falls back to $default.
     *
     * @param  array<int, string>  $allowed
     */
    protected function restrictSorting($request, array $allowed, string $default): void
    {
        if (! in_array($request->input('sort_by'), $allowed, true)) {
            $request->merge(['sort_by' => $default]);
        }
    }

    protected function applySearch($query, $searchTerm, $searchableFields)
    {
        // Use full-text search if available
        if (config('database.default') === 'mysql' && $this->isFullTextSearchSupported()) {
            $query->where(function ($q) use ($searchTerm, $searchableFields) {
                foreach ($searchableFields as $field) {
                    $q->orWhereRaw("MATCH($field) AGAINST(? IN BOOLEAN MODE)", [$searchTerm]);
                }
            });
        } else {
            // Advanced LIKE search with wildcard on both sides for substring matching
            $query->where(function ($q) use ($searchTerm, $searchableFields) {
                foreach ($searchableFields as $field) {
                    $q->orWhere($field, 'like', "%{$searchTerm}%");
                }
            });
        }
    }

    protected function applyFilter($query, $filter, $value, $callback)
    {
        if (is_callable($callback)) {
            $callback($query, $value);
        } else {
            // Use whereIn for array values (better performance)
            if (is_array($value)) {
                $query->whereIn($callback, $value);
            } else {
                $query->where($callback, $value);
            }
        }
    }

    protected function applySorting($query, $column, $direction = 'asc')
    {
        $query->orderBy($column, $direction);
    }

    protected function executePagination($query, $request)
    {
        return $query->paginate($request->per_page ?? 10)
            ->onEachSide(1);
    }

    protected function isFullTextSearchSupported(): bool
    {
        // Check if full-text search is supported and enabled
        return config('database.default') === 'mysql' &&
               DB::connection()->getConfig('engine') === 'InnoDB';
    }

    protected function getIndexHintForSort(string $column): ?string
    {
        // Return appropriate index for common sort columns
        $indexes = [
            'created_at' => 'created_at_index',
            'updated_at' => 'updated_at_index',
            'name' => 'name_index',
        ];

        return $indexes[$column] ?? null;
    }
}

<?php

namespace CloudMonitor\APIFlow;

use ReflectionClass;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

trait Searchable
{
    /**
     * Search query against database.
     * $queryColumns must be set in order to search in any columns.
     * 
     * @return void
     */
    private function searchQuery(): void
    {
        if (! request()->has('query')) {
            return;
        }

        $this->query->where(function(Builder $query) {
            collect($this->queryColumns)->each(function($column) use(&$query) {
                strpos($column, '.') !== false
                    ? $this->searchForeign(
                        $query,
                        explode('.', $column)[0],
                        explode('.', $column)[1]
                    )
                    : (strpos($column, 'scope:') !== false
                        ? $this->searchScope($query, $column)
                        : $this->searchLocal($query, $column)
                    );
            });
        });
    }

    /**
     * Search in local table.
     * 
     * @param Builder $query
     * @param string $column
     * @return void
     */
    private function searchLocal(Builder $query, string $column): void
    {
        try {
            $class = $this->query->getModel()::class;
            $r = (new ReflectionClass($class))->getProperty('translatable');
            $r->setAccessible(true);

            in_array($column, $r->getValue(new ($class)))
                ? $this->query->orWhereTranslation($column, 'LIKE', '%'. request()->input('query') .'%')
                : $query->orWhere(DB::raw('LOWER('. $column .')'), 'LIKE', '%'. strtolower(request()->input('query')) .'%');
        }
        catch (\ReflectionException $e) {
            $query->orWhere(DB::raw('LOWER('. $column .')'), 'LIKE', '%'. strtolower(request()->input('query')) .'%');
        }
    }

    /**
     * Search in related tables.
     * 
     * @param Builder $query
     * @param string $table
     * @param string $column
     * @return void
     */
    private function searchForeign(Builder $query, string $table, string $column): void
    {
        try {
            $class = (new ($this->query->getModel()::class))->{$table}()->getRelated();
            $r = (new ReflectionClass($class))->getProperty('translatable');
            $r->setAccessible(true);

            in_array($column, $r->getValue(new ($class)))
                ? $query->orWhereHas($table, function(Builder $q) use($column, $table) {
                    $q->orWhereTranslation($column, 'LIKE', '%'. request()->input('query') .'%');
                })
                : $query->orWhereHas($table, function(Builder $q) use($column) {
                    $q->orWhere(DB::raw('LOWER('. $column .')'), 'LIKE', '%'. strtolower(request()->input('query')) .'%');
                });
        }
        catch (\ReflectionException $e) {
            $query->orWhereHas($table, function(Builder $q) use($column) {
                $q->where(DB::raw('LOWER('. $column .')'), 'LIKE', '%'. strtolower(request()->input('query')) .'%');
            });
        }
    }

    /**
     * Use scope for advanced search options.
     * 
     * @param Builder $query
     * @param string $scope
     * @return void
     */
    private function searchScope(Builder $query, string $scope): void
    {
        preg_match('/^scope:([a-zA-Z0-9]+),?(.*)/', $scope, $matches);
        $scope = $matches[1];

        $class = new ReflectionClass($this->query->getModel()::class);
        $method = $class->getMethod('scope'. ucfirst($scope));

        $params = collect(explode(',', $matches[2]))->map(function($param) use($method) {
            if (! request()->has($param)) {
                return null;
            }

            $type = collect($method->getParameters())->first(function($p) use($param) {
                return $p->name === $param;
            })->getType()->getName() ?? 'string';

            if ($type === 'array') {
                return explode(',', request()->input($param));
            }

            if ($type === 'int') {
                return (int) request()->input($param);
            }

            if ($type === 'bool') {
                return (bool) request()->input($param);
            }

            return request()->input($param);
        });

        $query->{$matches[1]}(request()->input('query'), ...$params->toArray());
    }
}
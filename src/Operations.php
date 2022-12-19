<?php

namespace CloudMonitor\APIFlow;

use ReflectionClass;

trait Operations
{
    /**
     * Set output limit with a maxmimum of 25 to avoid database overload.
     *
     * @return void
     */
    private function setLimit(): void
    {
        if (! request()->has('limit')) {
            return;
        }

        $limit = request()->input('limit') <= $this->maxlimit
            ? request()->input('limit')
            : $this->limit;

        $this->query->limit($limit);
    }

    /**
     * Exclude IDs from result.
     * 
     * @return void
     */
    private function excludeIds(): void
    {
        if (! request()->has('exclude')) {
            return;
        }

        $this->query->whereNotIn('id', explode(',', request()->input('exclude')));
    }

    /**
     * Order output.
     *
     * @return void
     */
    private function orderBy(): void
    {
        $params = $this->checkParams('orderBy', implode(',', $this->defaultOrderBy));

        collect(explode(',', $params))->each(function($param) {
            @list($column, $direction) = explode('|', $param);

            if (! in_array($column, $this->orderBy ?? [])) {
                return;
            }

            try {
                $r = (new ReflectionClass($this->query->getModel()::class))->getProperty('translatable');
                $r->setAccessible(true);

                in_array($column, $r->getValue(new ($this->query->getModel()::class)))
                    ? $this->query->orderByTranslation($column, $direction ?? 'asc')
                    : $this->query->orderBy($column, $direction ?? 'asc');
            }
            catch (\ReflectionException $e) {
                $this->query->orderBy($column, $direction ?? 'asc');
            }
        });
    }

    /**
     * Check if parameter is present or default value should be used.
     * Or null in case neither is present.
     *
     * @param string $identifier
     * @param mixed $default
     * @return mixed
     */
    private function checkParams(string $identifier, mixed $default = ''): mixed
    {
        if (! request()->has($identifier) && ! $default) {
            return null;
        }

        if (request()->has($identifier)) {
            return request()->input($identifier);
        }

        return $default;
    }

    /**
     * Only include IDs from list.
     * 
     * @return void
     */
    private function only(): void
    {
        if (! request()->has('only')) {
            return;
        }

        if (! request()->has('limit')) {
            request()->merge(['limit' => 15]);
        }

        $this->query->whereIn('id', explode(',', request()->input('only')));
    }

    /**
     * Load relationships into the API output.
     * 
     * @return void
     */
    private function loadRelationships(): void
    {
        if (! request()->has('with')) {
            return;
        }

        collect(explode(',', request()->input('with')))->each(function($name) {
            if (! in_array($name, $this->withRelations)) {
                return;
            }

            $this->query->with($name);
        });
    }
}
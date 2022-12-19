<?php

namespace CloudMonitor\APIFlow;

use ReflectionMethod;
use Illuminate\Support\Collection;

trait Scopes
{
    /**
     * Call model custom scopes.
     * 
     * @return void
     */
    private function modelScopes(): void
    {
        collect($this->scopes)->each(function($scope) {
            if (! request()->has($scope)) {
                return;
            }

            $this->scopeParameters($scope)->count() === 1
                ? $this->query->{$scope}()
                : $this->query->{$scope}($this->formattedScopeValue($scope));
        });
    }

    /**
     * Scope methods parameters.
     * 
     * @param string $scope
     * @return Collection
     */
    private function scopeParameters(string $scope): Collection
    {
        return collect((new ReflectionMethod(get_class($this->query->getModel()), 'scope'. $scope))->getParameters());
    }

    /**
     * Type formatted scope value matched with scope method.
     * 
     * @param string $scope
     * @return mixed
     */
    private function formattedScopeValue(string $scope): mixed
    {
        $type = $this->scopeParameters($scope)[1]->getType()->getName();

        if ($type === 'array') {
            $array = explode(',', request()->input($scope));

            return count($array) === 1 && $array[0] === ''
                ? []
                : $array;
        }

        if ($type === 'int') {
            return (int) request()->input($scope);
        }

        if ($type === 'bool') {
            return (bool) request()->input($scope);
        }

        return request()->input($scope);
    }
}
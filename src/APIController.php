<?php

namespace CloudMonitor\APIFlow;

use ReflectionMethod;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class APIController extends Controller
{
    /**
     * Search query database columns.
     * 
     * @var array
     */
    protected $queryColumns = [];

    /**
     * Model scopes exposed to the API.
     * 
     * @var array
     */
    protected $scopes = [];

    /**
     * Mutabla query.
     * 
     * @var Collection
     */
    private $query;

    /**
     * Run query against API.
     * 
     * @param string $resourceClass
     * @param string $modelClass
     * @return mixed
     */
    public function api(string $resourceClass = null, string $modelClass = null): mixed
    {
        $this->query = $this->predictModelClass($modelClass)::query();
        $this->excludeIds();
        $this->setLimit();
        $this->searchQuery();
        $this->loadRelationships();
        $this->modelScopes();

        return $this->predictResourceClass($resourceClass)::collection(
            request()->has('limit')
                ? $this->query
                : $this->query->paginate()
        );
    }

    /**
     * Determine controller basename. UserController will translate to User.
     * 
     * @return string
     */
    private function basename(): string
    {
        return basename(str_ireplace(['\\', 'controller'], ['/', ''], get_class($this)));
    }

    /**
     * Predict resource class path from default location.
     * 
     * @param string $resourceClass
     * @return string
     */
    private function predictResourceClass(string $resourceClass = null): string
    {
        return $resourceClass
            ? $resourceClass
            : 'App\\Http\\Resources\\'. $this->basename();
    }

    /**
     * Predict model class path from default location.
     * 
     * @param string $modelClass
     * @return string
     */
    private function predictModelClass(string $modelClass = null): string
    {
        return $modelClass
            ? $modelClass
            :  'App\\Models\\'. $this->basename();
    }

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

        $limit = request()->input('limit') <= 25
            ? request()->input('limit')
            : 25;

        $this->query->limit($limit);
    }

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
                $query->orWhere(DB::raw('LOWER('. $column .')'), 'LIKE', '%'. strtolower(request()->input('query')) .'%');
            });
        });
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
            $this->query->with($name);
        });
    }

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
     * @return \Illuminate\Support\Collection
     */
    private function scopeParameters(string $scope): \Illuminate\Support\Collection
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

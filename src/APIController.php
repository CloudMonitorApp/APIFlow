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
     * Related models API can include.
     * 
     * @var array
     */
    protected $withRelations = [];
    
    /**
     * Order by columns.
     *
     * @var array
     */
    protected $orderBy;

    /**
     * Mutabla query.
     * 
     * @var Collection
     */
    private $query;

    /**
     * API Resource.
     * 
     * @var string
     */
    private $resourceClass;

    /**
     * Instantiate API.
     * 
     * @param string $resourceClass
     * @param string $modelClass
     * @return APIController
     */
    public function api(string $resourceClass = null, string $modelClass = null): APIController
    {
        $this->resourceClass = $resourceClass;
        $this->query = $this->predictModelClass($modelClass)::query();
        $this->loadRelationships();
        $this->modelScopes();

        return $this;
    }

    /**
     * Resource for index page.
     * 
     * @return mixed
     */
    public function many(): mixed
    {
        $this->excludeIds();
        $this->only();
        $this->setLimit();
        $this->searchQuery();
        $this->orderBy();

        return $this->predictResourceClass()::collection(
            request()->has('limit')
                ? $this->query->get()
                : $this->query->paginate()
        );
    }

    /**
     * See ->many().
     * 
     * @return mixed
     */
    
    public function getIndex(): mixed
    {
        return $this->many();
    }

    /**
     * Resource for show page.
     * 
     * @return mixed
     */
    public function one(int $id): mixed
    {
        $resource = $this->predictResourceClass();
        return new $resource($this->query->findOrFail($id));
    }

    /**
     * See ->one().
     * 
     * @return mixed
     */
    
    public function getShow($id): mixed
    {
        return $this->one($id);
    }

    /**
     * Apply additional queries.
     * 
     * @param callable $query
     * @return mixed
     */
    public function apply(callable $query): mixed
    {
        $this->query = $query($this->query);

        return $this;
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
     * @return string
     */
    private function predictResourceClass(): string
    {
        return $this->resourceClass
            ? $this->resourceClass
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
     * Order output.
     *
     * @return void
     */
    private function orderBy(): void
    {
        if (! request()->has('order')) {
            return;
        }
        
        collect(explode(',', request()->input('orderBy')))->each(function($param) {
            @list($column, $direction) = explode('|', $param);
            
            if (! in_array($column, $this->orderBy)) {
                return;
            }
            
            $this->query->orderBy($column, $direction ?? 'asc');
        });
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
            request()->request->add(['limit' => 15]);
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

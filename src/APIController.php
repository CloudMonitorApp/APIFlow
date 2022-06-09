<?php

namespace CloudMonitor\APIFlow;

use Closure;
use ReflectionClass;
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
     * @param array $params
     */
    public function __construct($params = [])
    {
        request()->merge($params);
    }

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
    public function many(array $defaults = []): mixed
    {
        $this->excludeIds();
        $this->only();
        $this->setLimit();
        $this->searchQuery();
        $this->orderBy($defaults['orderBy'] ?? null);

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
    public function apply(callable $scope): APIController
    {
        #$this->applied = $scope;
        $this->query = $scope($this->query);

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
     * @param array $default
     * @return void
     */
    private function orderBy(?string $default = ''): void
    {
        $params = $this->checkParams('orderBy', $default);
        
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

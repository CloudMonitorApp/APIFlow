<?php

namespace CloudMonitor\APIFlow;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;

class APIController extends Controller
{
    use Searchable, Endpoints, Scopes, Operations;

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
     * Default order columns.
     *
     * @var array
     */
    protected $defaultOrderBy = ['name', 'email'];

    /**
     * Default limit when not given as parameter.
     * 
     * @var int
     */
    protected $limit = 25;

    /**
     * Maximum value a limit can be set to.
     * 
     * @var int
     */
    protected $maxlimit = 25;

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

    private $modelClass;

    /**
     * @param array $params
     */
    public function __construct($params = [], string $resourceClass = null, string $modelClass = null)
    {
        request()->merge($params);
        $this->resourceClass = $resourceClass;
        $this->modelClass = $modelClass;
        $this->query = $this->model()::query();
        $this->loadRelationships();
        $this->modelScopes();
    }

    /**
     * Get predicted model.
     * Only works for default setup with no custom model.
     * 
     * @return string
     */
    public function model(): string
    {
        return $this->modelClass
            ? $this->modelClass
            :  'App\\Models\\'. $this->basename();
    }

    /**
     * 
     */
    public function parameter(): string
    {
        $path = explode('\\', $this->model());
        $last = count($path) - 1;
        return strtolower($path[$last]);
    }

    /**
     * Apply additional queries.
     * 
     * @param callable $query
     * @return mixed
     */
    public function apply(callable $scope): APIController
    {
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
}

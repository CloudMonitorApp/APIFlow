<?php

namespace CloudMonitor\APIFlow;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\ResourceCollection;

class APICollection extends ResourceCollection
{
    /**
     * The model authorization is bound to.
     * 
     * @var string
     */
    private $model;

    private $meta = [];

    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @param string  $resourceClass
     * @param string  $model
     * @return void
     */
    public function __construct($resource, $resourceClass, $model)
    {
        $this->collects = $resourceClass;
        $this->model = $model;
        parent::__construct($resource);

        if (method_exists($this, 'meta')) {
            $this->meta = $this->meta();
        }
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function with($request)
    {
        return [
            'can' => [
                'create' => Auth::user()->can('create', $this->model),
                'viewAny' => Auth::user()->can('viewAny', $this->model),
            ],
            'meta' => $this->meta,
        ];
    }
}

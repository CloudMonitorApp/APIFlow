<?php

namespace CloudMonitor\APIFlow;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\JsonResource;

class APIResource extends JsonResource
{
    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource)
    {
        $resource->can = [
            'update' => Auth::user()->can('update', $resource),
            'delete' => Auth::user()->can('delete', $resource),
        ];

        parent::__construct($resource);
    }
}

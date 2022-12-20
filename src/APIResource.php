<?php

namespace CloudMonitor\APIFlow;

use App\Models\Role;
use App\Models\User;
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
        foreach ($resource->getOriginal() as $key => $value) {
            if (substr($key, 0, 6) === 'pivot_') {
                parent::__construct($resource);
                return;
            }
        }

        $resource->can = [
            'update' => Auth::user()->can('update', $resource),
            'delete' => Auth::user()->can('delete', $resource),
        ];

        parent::__construct($resource);
    }
}

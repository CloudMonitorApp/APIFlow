<?php

namespace CloudMonitor\APIFlow;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

trait Endpoints
{
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

        $class = class_exists($this->predictResourceClass() .'Collection')
            ? $this->predictResourceClass() .'Collection'
            : 'APICollection';

        $collection = new $class(
            request()->has('limit')
                ? $this->query->get()
                : $this->query->paginate(),
            $this->predictResourceClass(),
            $this->model()
        );

        return $collection;
    }

    /**
     * 
     */
    public function make(Request $request = null)
    {
        $object = $this->model()::create($request ? $request->all() : request()->all());
        return $this->one($object->id);
    }

    /**
     * 
     */
    public function change(Model $model, $request = null)
    {
        $model->update($request ? $request->all() : request()->all());
        return $this->one($model->id);
    }

    /**
     * 
     */
    public function delete(Model $model)
    {
        $response = $this->one($model->id);
        return $response;
    }
}

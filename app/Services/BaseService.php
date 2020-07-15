<?php
/**
 * Created by PhpStorm.
 * User: quanvn
 * Date: 1/15/20
 * Time: 9:43 AM
 */

namespace App\Services;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;


abstract class BaseService
{
    const TIME_STAMP = ['created_at', 'updated_at', 'deleted_at'];

    /** @var $model Model */
    protected $model;

    /** @var Builder $query */
    protected $query;

    public function __construct()
    {
        $this->setModel();
        $this->setQuery();
    }

    abstract protected function setModel();

    private function setQuery()
    {
        $this->query = $this->model->query();
    }

    public function findAll($columns = ['*'])
    {
        return $this->query->get(is_array($columns) ? $columns : func_get_args());
    }

    /**
     * Retrieve the specified resource.
     *
     * @param int $id
     * @param array $relations
     * @param array $appends
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function show($id, array $relations = [], array $appends = [], array $hiddens = [], $withTrashed = false)
    {
        if ($withTrashed) {
            $this->query->withTrashed();
        }
        return $this->query->with($relations)->findOrFail($id)->setAppends($appends)->makeHidden($hiddens);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param array $attributes
     * @return \Illuminate\Database\Eloquent\Model|bool
     */
    public function store(array $attributes)
    {
        $parent = $this->query->create($attributes);

        foreach (array_filter($attributes, 'is_array') as $key => $models) {
            if (method_exists($parent, $relation = Str::camel($key))) {

                $models = $parent->$relation() instanceof HasOne ? [$models] : $models;

                foreach (array_filter($models) as $model) {
                    $parent->setRelation($key, $parent->$relation()->make($model));
                }

            }
        }

        return $parent->push() ? $parent : false;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int $id
     * @param array $attributes
     * @return \Illuminate\Database\Eloquent\Model|bool
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function update($id, array $attributes)
    {
        $this->setQuery();
        $parent = $this->query->findOrFail($id)->fill($attributes);

        foreach (array_filter($attributes, 'is_array') as $key => $models) {
            if (method_exists($parent, $relation = Str::camel($key))) {

                $models = $parent->$relation() instanceof HasOne ? [$models] : $models;

                foreach (array_filter($models) as $model) {
                    /** @var \Illuminate\Database\Eloquent\Model $relationModel */

                    if (isset($model['id'])) {
                        $relationModel = $parent->$relation()->findOrFail($model['id']);
                    } else {
                        $relationModel = $parent->$relation()->make($model);
                    }
                    $parent->setRelation($key, $relationModel->fill($model));
                }

            }
        }

        return $parent->push() ? $parent : false;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return bool
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException|Exception
     */
    public function destroy($id)
    {
        return $this->query->findOrFail($id)->delete();
    }

    public function restore($id)
    {
        return $this->query->withTrashed()->findOrFail($id)->restore();
    }

    /**
     * @param array $attrs
     * @return Builder|Model|null|object
     */
    public function findBy(array $attrs)
    {
        return $this->model->query()->where($attrs)->first();
    }

    public function firstOrCreate(array $attributes, array $values = [])
    {
        return $this->model->query()->firstOrCreate($attributes, $values);
    }

    public function updateOrCreate(array $attributes, array $values = [])
    {
        return $this->model->query()->updateOrCreate($attributes, $values);
    }

    public function buildBasicQuery($params, array $relations = [], $withTrashed = false)
    {
        $params = $params ?: request()->toArray();
        if ($relations && count($relations)) {
            $this->query->with($relations);
        }
        if ($withTrashed) {
            $this->query->withTrashed();
        }
        if (method_exists($this, 'addFilter')) {
            $this->addFilter();
        }
        $this->addDefaultFilter($params);
        $data = $this->query->paginate(isset($params['limit']) ? $params['limit'] : 20);
        return $data;
    }

    protected function addDefaultFilter($params)
    {
        $params = $params ?: request()->toArray();
        if (isset($params['filter']) && $params['filter']) {
            $filters = json_decode($params['filter'], true);
            foreach ($filters as $key => $filter) {
                $this->basicFilter($this->query, $key, $filter);
            }
        }
        if (isset($params['sort']) && $params['sort']) {
            $sort = explode('|', $params['sort']);
            if ($sort && count($sort) == 2) {
                $this->query->orderBy($sort[0], $sort[1]);
            }
        }
    }

    protected function basicFilter(Builder $query, $key, $filter)
    {
        if (is_array($filter)) {
            if ($key == 'equal') {
                foreach ($filter as $index => $value) {
                    if ($this->checkParamFilter($value)) {
                        $query->where($index, $value);
                    }
                }
            } else if ($key == 'like') {
                foreach ($filter as $index => $value) {
                    if ($this->checkParamFilter($value)) {
                        $query->where($index, 'LIKE', '%' . $value . '%');
                    }
                }
            } else if ($key == 'range') {
                foreach ($filter as $index => $value) {
                    if ($this->checkParamFilter($value)) {
                        if (is_array($value) && count($value) == 2 && in_array($index, self::TIME_STAMP)) {
                            $query->whereBetween($index, $value);
                        }
                    }
                }
            } else if ($key == 'relation') {
                foreach ($filter as $relation => $relationFilters) {
                    if (is_array($relationFilters) && count($relationFilters)) {
                        foreach ($relationFilters as $index => $value) {
                            if ($value && count($value)) {
                                $query->whereHas($relation, function ($builder) use ($index, $value) {
                                    $this->basicFilter($builder, $index, $value);
                                });
                            }
                        }
                    }
                }
            } else {
                if (count($filter)) {
                    $query->whereIn($key, $filter);
                }
            }
        } else {
            $query->where($key, 'LIKE', '%' . $filter . '%');
        }
    }

    protected function checkParamFilter($value)
    {
        return $value != '' && $value != null;
    }
}

<?php
namespace J6s\SimpleMysqlScoutDriver\Engine;

use Illuminate\Database\Eloquent\Model;
use J6s\SimpleMysqlScoutDriver\Exception\NotImplementedException;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use Laravel\Scout\Searchable;

class SimpleMysqlEngine extends Engine
{

    protected function getQueryForModel($model)
    {
        return \DB::table('search_index')
            ->where('model', get_class($model))
            ->where('model_id', $model->id);
    }

    /**
     * Update the given model in the index.
     *
     * @param  \Illuminate\Database\Eloquent\Collection $models
     * @return void
     */
    public function update($models)
    {
        foreach ($models as $model) {
            /** @var Searchable $model */
            $exists = $this->getQueryForModel($model)->count() > 0;
            $fields = $model->toSearchableArray();
            if ($exists) {
                $this->getQueryForModel($model)->update([
                    'index' => implode(' ', array_values($fields))
                ]);
            } else {
                $this->getQueryForModel($model)->insert([
                    'model' => get_class($model),
                    'model_id' => $model->id,
                    'index' => implode(' ', array_values($fields))
                ]);
            }
        }
    }

    /**
     * Remove the given model from the index.
     *
     * @param  \Illuminate\Database\Eloquent\Collection $models
     * @return void
     */
    public function delete($models)
    {
        foreach ($models as $model) {
            $exists = $this->getQueryForModel($model)->count() > 0;
            if ($exists) {
                $this->getQueryForModel($model)->delete();
            }
        }
    }

    /**
     * Performs the given search and returns an array of matching ids
     * @param Builder $builder
     * @return array
     */
    protected function performSearch(Builder $builder)
    {
        $query = \DB::table('search_index')
            ->where('model', get_class($builder->model));

        switch (config('scout.mysql_simple.mode')) {
            case 'LIKE':
                $query->where('index', 'LIKE', '%' . $builder->query . '%');
                break;

            case 'NATURAL':
            default:
                $query->whereRaw('MATCH(`index`) AGAINST(? IN NATURAL LANGUAGE MODE)', [ $builder->query ]);
                break;
        }

        return $query
            ->select('model_id')
            ->get()
            ->map(function($row) { return $row->model_id; })
            ->toArray();
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  \Laravel\Scout\Builder $builder
     * @return mixed
     */
    public function search(Builder $builder)
    {
        return $this->performSearch($builder);
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  \Laravel\Scout\Builder $builder
     * @param  int $perPage
     * @param  int $page
     * @return mixed
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        throw new NotImplementedException();
    }

    /**
     * Pluck and return the primary keys of the given results.
     *
     * @param  mixed $results
     * @return \Illuminate\Support\Collection
     */
    public function mapIds($results)
    {
        // TODO: Implement mapIds() method.
        throw new NotImplementedException();
    }

    /**
     * Map the given results to instances of the given model.
     *
     * @param  mixed $results
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function map($results, $model)
    {
        $class = get_class($model);
        return $class::whereIn('id', $results)->get();
    }

    /**
     * Get the total count from a raw result returned by the engine.
     *
     * @param  mixed $results
     * @return int
     */
    public function getTotalCount($results)
    {
        return array_count_values($results);
    }
}
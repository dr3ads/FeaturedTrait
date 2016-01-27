<?php

namespace NST\Traits\Featured;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ScopeInterface;

class FeaturedScope implements ScopeInterface
{
    /**
     * All of the extensions to be added to the builder.
     *
     * @var array
     */
    //protected $extensions = ['ForceDelete', 'Restore', 'WithTrashed', 'OnlyTrashed'];
    protected $extensions = ['DoFeature','UnFeature', 'OnlyFeatured', 'WithFeatured'];
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        //$builder->whereNull($model->getQualifiedFeaturedAtColumn());

        $this->extend($builder);
    }

    /**
     * Remove the scope from the given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function remove(Builder $builder, Model $model)
    {
        $column = $model->getQualifiedFeaturedAtColumn();

        $query = $builder->getQuery();
        return $query;
        $query->wheres = collect($query->wheres)->reject(function ($where) use ($column) {
            return $this->isFeaturedConstraint($where, $column);
        })->values()->all();
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    public function extend(Builder $builder)
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }


        /*$builder->onDelete(function (Builder $builder) {
            $column = $this->getFeaturedAtColumn($builder);

            return $builder->update([
                $column => $builder->getModel()->freshTimestampString(),
            ]);
        });*/
    }

    /**
     * Get the "deleted at" column for the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return string
     */
    protected function getFeaturedAtColumn(Builder $builder)
    {
        if (count($builder->getQuery()->joins) > 0) {
            return $builder->getModel()->getQualifiedFeaturedAtColumn();
        } else {
            return $builder->getModel()->getFeaturedAtColumn();
        }
    }

    /**
     * Add the with-trashed extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addWithFeatured(Builder $builder)
    {
        $builder->macro('withFeatured', function (Builder $builder) {
            $this->remove($builder, $builder->getModel());

            return $builder;
        });
    }

    /**
     * Add the restore extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addUnFeature(Builder $builder)
    {
        $builder->macro('unfeature', function (Builder $builder) {
            $builder->withFeatured();

            return $builder->update([$builder->getModel()->getFeaturedAtColumn() => null]);
        });
    }

    /**
     * Add the restore extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addDoFeature(Builder $builder)
    {
        $builder->macro('dofeature', function (Builder $builder) {
            return $builder->update([$builder->getModel()->getFeaturedAtColumn() => $builder->getModel()->freshTimestampString()]);
        });
    }

    /**
     * Add the only-trashed extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addOnlyFeatured(Builder $builder)
    {
        $builder->macro('onlyFeatured', function (Builder $builder) {
            $model = $builder->getModel();

            $this->remove($builder, $model);

            $builder->getQuery()->whereNotNull($model->getQualifiedFeaturedAtColumn());

            return $builder;
        });
    }

    /**
     * Determine if the given where clause is a soft delete constraint.
     *
     * @param  array   $where
     * @param  string  $column
     * @return bool
     */
    protected function isFeaturedConstraint(array $where, $column)
    {
        return $where['type'] == 'Null' && $where['column'] == $column;
    }
}

<?php

namespace NST\Traits\Featured;

trait Featured
{

    /**
     * Boot the featured trait for a model.
     *
     * @return void
     */
    public static function bootFeatured()
    {
        static::addGlobalScope(new FeaturedScope);
    }

    public function doFeature()
    {
        $this->runFeatured();
    }


    /**
     * Perform the actual delete query on this model instance.
     *
     * @return void
     */
    protected function runFeatured()
    {
        $query = $this->newQueryWithoutScopes()->where($this->getKeyName(), $this->getKey());

        $this->{$this->getFeaturedAtColumn()} = $time = $this->freshTimestamp();

        $query->update([$this->getFeaturedAtColumn() => $this->fromDateTime($time)]);
    }

    /**
     * Restore a soft-deleted model instance.
     *
     * @return bool|null
     */
    public function unfeature()
    {
        // If the restoring event does not return false, we will proceed with this
        // restore operation. Otherwise, we bail out so the developer will stop
        // the restore totally. We will clear the deleted timestamp and save.
        if ($this->fireModelEvent('unfeaturing') === false) {
            return false;
        }

        $this->{$this->getFeaturedAtColumn()} = null;

        // Once we have saved the model, we will fire the "restored" event so this
        // developer will do anything they need to after a restore operation is
        // totally finished. Then we will return the result of the save call.
        $this->exists = true;

        $result = $this->save();

        $this->fireModelEvent('unfeatured', false);

        return $result;
    }

    /**
     * Determine if the model instance has been soft-deleted.
     *
     * @return bool
     */
    public function featured()
    {
        return ! is_null($this->{$this->getFeaturedAtColumn()});
    }

    /**
     * Get a new query builder that includes soft deletes.
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public static function withFeatured()
    {
        return (new static)->newQueryWithoutScope(new FeaturedScope);
    }

    /**
     * Get a new query builder that only includes soft deletes.
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public static function onlyFeatured()
    {
        $instance = new static;

        $column = $instance->getQualifiedFeaturedAtColumn();

        return $instance->newQueryWithoutScope(new FeaturedScope)->whereNotNull($column);
    }

    /**
     * Register a restoring model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function unfeaturing($callback)
    {
        static::registerModelEvent('unfeaturing', $callback);
    }

    /**
     * Register a restored model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function unfeatured($callback)
    {
        static::registerModelEvent('unfeatured', $callback);
    }

    /**
     * Get the name of the "deleted at" column.
     *
     * @return string
     */
    public function getFeaturedAtColumn()
    {
        return defined('static::FEATURED_AT') ? static::FEATURED_AT : 'featured_at';
    }

    /**
     * Get the fully qualified "deleted at" column.
     *
     * @return string
     */
    public function getQualifiedFeaturedAtColumn()
    {
        return $this->getTable().'.'.$this->getFeaturedAtColumn();
    }
}

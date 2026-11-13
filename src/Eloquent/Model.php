<?php

namespace Hoyvoy\CrossDatabase\Eloquent;

use Illuminate\Database\Eloquent\Model as IlluminateModel;

abstract class Model extends IlluminateModel
{
    /**
     * Fully qualified "from" for relation subqueries, set by the Builder only
     * on throwaway related instances when the parent lives in another database.
     */
    protected ?string $crossDatabaseFrom = null;

    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    public function crossDatabaseFrom(?string $from): static
    {
        $this->crossDatabaseFrom = $from;

        return $this;
    }

    public function newModelQuery()
    {
        $query = parent::newModelQuery();

        if ($this->crossDatabaseFrom !== null) {
            $query->getQuery()->from($this->crossDatabaseFrom);
        }

        return $query;
    }
}

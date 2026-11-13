<?php

namespace Hoyvoy\CrossDatabase\Eloquent;

use Illuminate\Database\Eloquent\Builder as IlluminateBuilder;
use LogicException;

/**
 * Every relation subquery (has, whereHas, doesntHave, withCount, withSum, ...)
 * starts from getRelationWithoutConstraints(); we mark the related model there
 * so its subquery selects from `database`.`table` when the databases differ.
 */
class Builder extends IlluminateBuilder
{
    protected function getRelationWithoutConstraints($relation)
    {
        $relation = parent::getRelationWithoutConstraints($relation);
        $related = $relation->getRelated();

        if ($related instanceof Model && ($from = $this->crossDatabaseFrom($related)) !== null) {
            $related->crossDatabaseFrom($from);
        }

        return $relation;
    }

    protected function crossDatabaseFrom(Model $related): ?string
    {
        $parent = $this->getConnection();
        $sub = $related->getConnection();

        if ($parent->getName() === $sub->getName() || $parent->getDatabaseName() === $sub->getDatabaseName()) {
            return null;
        }

        $table = $related->getTable();

        // Already qualified by the user, or a driver that cannot do it: leave as is.
        if (str_contains($table, '.')) {
            return null;
        }

        $driver = $parent->getDriverName();

        if ($driver === 'pgsql') {
            throw new LogicException('PostgreSQL cannot query across databases (use dblink/postgres_fdw or schemas).');
        }

        if (!in_array($driver, ['mysql', 'mariadb', 'sqlsrv'], true) || $sub->getDriverName() !== $driver) {
            return null;
        }

        // SQL Server needs a schema in 3-part names; `dbo` is the default one.
        return $sub->getDatabaseName().($driver === 'sqlsrv' ? '.dbo.' : '.').$table;
    }
}

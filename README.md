# Laravel Cross database subqueries
Makes Eloquent relation subqueries work when the related model lives in another database on the same server.

Works with: `has`, `whereHas`, `doesntHave`, `whereDoesntHave`, `withCount`, `withSum/Avg/Min/Max/Exists`.

# Installation
~~~
composer require hoyvoy/laravel-cross-database-subqueries
~~~
No service provider needed. Requires PHP 8.2+, Laravel 11/12.

# Usage
Extend `Hoyvoy\CrossDatabase\Eloquent\Model` in your models. When the parent and related models use
connections with different databases, the subquery selects from `db.table` (SQL Server: `db.dbo.table`).

# Limits
* MySQL/MariaDB and SQL Server only. PostgreSQL cannot query across databases, so a `LogicException` is thrown (use schemas, dblink or postgres_fdw). SQLite is left untouched.
* Both connections must point at the same server.
* Pivot tables of `belongsToMany`/`hasManyThrough` and table prefixes are not rewritten.
* A table you already qualified yourself (`db.table`) is left as is.

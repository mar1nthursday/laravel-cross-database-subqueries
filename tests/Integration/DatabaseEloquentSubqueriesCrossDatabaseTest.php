<?php

namespace Hoyvoy\Tests\Integration;

use Hoyvoy\CrossDatabase\Eloquent\Model as Model;
use Hoyvoy\Tests\TestCase;
use LogicException;

class DatabaseEloquentSubqueriesCrossDatabaseTest extends TestCase
{
    public static function relations(): array
    {
        $like = fn ($q) => $q->where('name', 'like', '%a%');

        return [
            'mysql whereHas cross'   => [fn () => UserMysql::whereHas('orders', $like), 'select * from `users` where exists (select * from `mysql2`.`orders` where `users`.`id` = `orders`.`user_id` and `name` like ?)'],
            'mysql whereHas same'    => [fn () => UserMysql::whereHas('posts', $like), 'select * from `users` where exists (select * from `posts` where `users`.`id` = `posts`.`user_id` and `name` like ?)'],
            'mysql has cross'        => [fn () => UserMysql::has('orders'), 'select * from `users` where exists (select * from `mysql2`.`orders` where `users`.`id` = `orders`.`user_id`)'],
            'mysql has count cross'  => [fn () => UserMysql::has('orders', '>=', 2), 'select * from `users` where (select count(*) from `mysql2`.`orders` where `users`.`id` = `orders`.`user_id`) >= 2'],
            'mysql doesntHave cross' => [fn () => UserMysql::doesntHave('orders'), 'select * from `users` where not exists (select * from `mysql2`.`orders` where `users`.`id` = `orders`.`user_id`)'],
            'mysql whereDoesntHave'  => [fn () => UserMysql::whereDoesntHave('orders', $like), 'select * from `users` where not exists (select * from `mysql2`.`orders` where `users`.`id` = `orders`.`user_id` and `name` like ?)'],
            'mysql withCount cross'  => [fn () => UserMysql::withCount(['orders' => $like]), 'select `users`.*, (select count(*) from `mysql2`.`orders` where `users`.`id` = `orders`.`user_id` and `name` like ?) as `orders_count` from `users`'],
            'mysql withCount alias'  => [fn () => UserMysql::withCount('orders as o'), 'select `users`.*, (select count(*) from `mysql2`.`orders` where `users`.`id` = `orders`.`user_id`) as `o` from `users`'],
            'mysql withSum cross'    => [fn () => UserMysql::withSum('orders', 'total'), 'select `users`.*, (select sum(`orders`.`total`) from `mysql2`.`orders` where `users`.`id` = `orders`.`user_id`) as `orders_sum_total` from `users`'],
            'mysql withCount same'   => [fn () => UserMysql::withCount('posts'), 'select `users`.*, (select count(*) from `posts` where `users`.`id` = `posts`.`user_id`) as `posts_count` from `users`'],
            'sqlsrv has cross'       => [fn () => UserSqlsrv::has('orders'), 'select * from [users] where exists (select * from [sqlsrv2].[dbo].[orders] where [users].[id] = [orders].[user_id])'],
            'sqlsrv has same'        => [fn () => UserSqlsrv::has('posts'), 'select * from [users] where exists (select * from [posts] where [users].[id] = [posts].[user_id])'],
            'sqlsrv withCount cross' => [fn () => UserSqlsrv::withCount('orders'), 'select [users].*, (select count(*) from [sqlsrv2].[dbo].[orders] where [users].[id] = [orders].[user_id]) as [orders_count] from [users]'],
            'pgsql has same'         => [fn () => UserPgsql::has('posts'), 'select * from "users" where exists (select * from "posts" where "users"."id" = "posts"."user_id")'],
            'sqlite has (untouched)' => [fn () => UserSqlite::has('orders'), 'select * from "users" where exists (select * from "orders" where "users"."id" = "orders"."user_id")'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('relations')]
    public function testSql(\Closure $query, string $sql)
    {
        $this->assertSame($sql, $query()->toSql());
    }

    public function testPostgresCrossDatabaseThrows()
    {
        $this->expectException(LogicException::class);

        UserPgsql::has('orders');
    }
}

class UserMysql extends Model
{
    protected $connection = 'mysql1';
    protected $table = 'users';
    protected $guarded = [];

    public function orders()
    {
        return $this->hasMany('Hoyvoy\Tests\Integration\OrderMysql', 'user_id');
    }

    public function posts()
    {
        return $this->hasMany('Hoyvoy\Tests\Integration\PostMysql', 'user_id');
    }
}

class PostMysql extends Model
{
    protected $connection = 'mysql1';
    protected $table = 'posts';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo('Hoyvoy\Tests\Integration\UserMysql', 'user_id');
    }
}

class OrderMysql extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'orders';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo('Hoyvoy\Tests\Integration\UserMysql', 'user_id');
    }
}

class UserPgsql extends Model
{
    protected $connection = 'pgsql1';
    protected $table = 'users';
    protected $guarded = [];

    public function orders()
    {
        return $this->hasMany('Hoyvoy\Tests\Integration\OrderPgsql', 'user_id');
    }

    public function posts()
    {
        return $this->hasMany('Hoyvoy\Tests\Integration\PostPgsql', 'user_id');
    }
}

class PostPgsql extends Model
{
    protected $connection = 'pgsql1';
    protected $table = 'posts';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo('Hoyvoy\Tests\Integration\UserPgsql', 'user_id');
    }
}

class OrderPgsql extends Model
{
    protected $connection = 'pgsql2';
    protected $table = 'orders';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo('Hoyvoy\Tests\Integration\UserPgsql', 'user_id');
    }
}

class UserSqlsrv extends Model
{
    protected $connection = 'sqlsrv1';
    protected $table = 'users';
    protected $guarded = [];

    public function orders()
    {
        return $this->hasMany('Hoyvoy\Tests\Integration\OrderSqlsrv', 'user_id');
    }

    public function posts()
    {
        return $this->hasMany('Hoyvoy\Tests\Integration\PostSqlsrv', 'user_id');
    }
}

class PostSqlsrv extends Model
{
    protected $connection = 'sqlsrv1';
    protected $table = 'posts';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo('Hoyvoy\Tests\Integration\UserSqlsrv', 'user_id');
    }
}

class OrderSqlsrv extends Model
{
    protected $connection = 'sqlsrv2';
    protected $table = 'orders';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo('Hoyvoy\Tests\Integration\UserSqlsrv', 'user_id');
    }
}

class UserSqlite extends Model
{
    protected $connection = 'sqlite1';
    protected $table = 'users';
    protected $guarded = [];

    public function orders()
    {
        return $this->hasMany('Hoyvoy\Tests\Integration\OrderSqlite', 'user_id');
    }

    public function posts()
    {
        return $this->hasMany('Hoyvoy\Tests\Integration\PostSqlite', 'user_id');
    }
}

class PostSqlite extends Model
{
    protected $connection = 'sqlite1';
    protected $table = 'posts';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo('Hoyvoy\Tests\Integration\UserSqlite', 'user_id');
    }
}

class OrderSqlite extends Model
{
    protected $connection = 'sqlite2';
    protected $table = 'orders';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo('Hoyvoy\Tests\Integration\UserSqlite', 'user_id');
    }
}

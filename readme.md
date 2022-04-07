## Laravel Oscar Database Package

### OscarDB (updated for Laravel 8.x)

[![Latest Stable Version]()](https://packagist.org/packages/microestc/oscardb) [![Total Downloads](https://poser.pugx.org/microestc/oscardb/downloads.png)](https://packagist.org/packages/microestc/oscardb) [![Build Status](microestc/Laravel-OscarDB.png)](https://travis-ci.org/microestc/Laravel-OscarDB)


OscarDB is an Oscar Database Driver package for [Laravel Framework](https://laravel.com)

_NOTE: This package has not been tested in PHP 8._

**Please report any bugs you may find.**

- [Installation](#installation)
- [Basic Usage](#basic-usage)
- [Unimplemented Features](#unimplemented-features)
- [License](#license)

### Installation

With [Composer](https://getcomposer.org):

```sh
composer require microestc/oscardb
```

During this command, Laravel's "Auto-Discovery" feature should automatically register OscarDB's service
provider.

Next, publish OscarDB's configuration file using the vendor:publish Artisan command. This will copy OscarDB's
configuration file to `config/oscardb.php` in your project.

```sh
php artisan vendor:publish --tag=oscardb-config
```

To finish the installation, set your environment variables (typically in your .env file) to the corresponding
env variables used in `config/oscardb.php`: such as `DB_HOST`, `DB_USERNAME`, etc.  

Additionally, it may be necessary for your app to configure the NLS_DATE_FORMAT of the database connection session, 
before any queries are executed. One way to accomplish this is to run a statement in your `AppServiceProvider`'s `boot` 
method, for example:

```php
if (config('database.default') === 'oscardb') {
	DB::statement("ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD HH24:MI:SS'");
}
```

### Basic Usage
The configuration file for this package is located at `config/oscardb.php`.
In this file, you define all of your Oscar database connections. If you need to make more than one connection, just
copy the example one. If you want to make one of these connections the default connection, enter the name you gave the
connection into the "Default Database Connection Name" section in `config/database.php`.

such as in config/database.php
```
'default' => 'oscardb',
```

Once you have configured the OscarDB database connection(s), you may run queries using the `DB` facade as normal.

_NOTE: ACI is the default driver. If you want to use the PDO_ACI driver, change the `driver` value to `'pdo'` in the `config/oscardb.php` file for whichever connections you wish to have utilize PDO_ACI. Setting the driver to `'pdo'` will make OscarDB use the [PDO_ACI](https://www.php.net/manual/en/ref.pdo-aci.php) extension. Given any other `driver` value, OscarDB will use the [ACI Functions](https://www.php.net/manual/en/ref.aci.php)._

```php
$results = DB::select('select * from users where id = ?', [1]);
```

The above statement assumes you have set the default connection to be the Oscar connection you setup in
config/database.php file and will always return an `array` of results.

```php
$results = DB::connection('oscardb')->select('select * from users where id = ?', [1]);
```

Just like the built-in database drivers, you can use the connection method to access the Oscar database(s) you setup
in config/oscardb.php file.

#### Inserting Records Into A Table With An Auto-Incrementing ID

```php
	$id = DB::connection('oscardb')->table('users')->insertGetId(
		['email' => 'john@example.com', 'votes' => 0], 'userid'
	);
```

> **Note:** When using the insertGetId method, you can specify the auto-incrementing column name as the second
parameter in insertGetId function. It will default to "id" if not specified.

See [Laravel Database Basic Docs](https://laravel.com/docs/8.x/database) for more information.

### Unimplemented Features

Some of the features available in the first-party Laravel database drivers are not implemented in this package. Pull 
requests are welcome for implementing any of these features, or for expanding this list if you find any unimplemented 
features not already listed.

#### Query Builder

- insertOrIgnore `DB::from('users')->insertOrIgnore(['email' => 'foo']);`
- insertGetId with empty values `DB::from('users')->insertGetId([]);` (but calling with non-empty values is supported)
- upserts `DB::from('users')->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], 'email');`
- deleting with a join `DB::from('users')->join('contacts', 'users.id', '=', 'contacts.id')->where('users.email', '=', 'foo')->delete();`
- deleting with a limit `DB::from('users')->where('email', '=', 'foo')->orderBy('id')->take(1)->delete();`
- json operations `DB::from('users')->where('items->sku', '=', 'foo-bar')->get();`

#### Schema Builder

- drop a table if it exists `Schema::dropIfExists('some_table');`
- drop all tables, views, or types `Schema::dropAllTables()`, `Schema::dropAllViews()`, and `Schema::dropAllTypes()`
- set collation on a table `$blueprint->collation('BINARY_CI')`
- set collation on a column `$blueprint->string('some_column')->collation('BINARY_CI')`
- set comments on a table `$blueprint->comment("This table is great.")`
- set comments on a column `$blueprint->string('foo')->comment("Some helpful info about the foo column")`
- set the starting value of an auto-incrementing column `$blueprint->increments('id')->startingValue(1000)`
- create a private temporary table `$blueprint->temporary()`
- rename an index `$blueprint->renameIndex('foo', 'bar')`
- specify an algorithm when creating an index via the third argument `$blueprint->index(['foo', 'bar'], 'baz', 'hash')`
- create a spatial index `$blueprint->spatialIndex('coordinates')`
- create a spatial index fluently `$blueprint->point('coordinates')->spatialIndex()`
- create a generated column, like the mysql driver has `virtualAs` and `storedAs` and postgres has `generatedAs`; ie, assuming an integer type column named price exists on the table, `$blueprint->integer('discounted_virtual')->virtualAs('price - 5')`
- create a json column `$blueprint->json('foo')` or jsonb column `$blueprint->jsonb('foo')` (Oscar recommends storing json in VARCHAR2, CLOB, or BLOB columns)
- create a datetime with timezone column without precision `$blueprint->dateTimeTz('created_at')`, or with precision `$blueprint->timestampTz('created_at', 1)`
- create Laravel-style timestamp columns having a timezone component `$blueprint->timestampsTz()`
- create a uuid column `$blueprint->uuid('foo')` (Oscar recommends a column of data type 16 byte raw for storing uuids)
- create a foreign uuid column `$blueprint->foreignUuid('foo')`
- create a column to hold IP addresses `$blueprint->ipAddress('foo')` (would be implemented as varchar2 45)
- create a column to hold MAC addresses `$blueprint->macAddress('foo')` (would be implemented as varchar2 17)
- create a geometry column `$blueprint->geometry('coordinates')`
- create a geometric point column `$blueprint->point('coordinates')`
- create a geometric point column specifying srid `$blueprint->point('coordinates', 4326)`
- create a linestring column `$blueprint->linestring('coordinates')`
- create a polygon column `$blueprint->polygon('coordinates')`
- create a geometry collection column `$blueprint->geometrycollection('coordinates')`
- create a multipoint column `$blueprint->multipoint('coordinates')`
- create a multilinestring column `$blueprint->multilinestring('coordinates')`
- create a multipolygon column `$blueprint->multipolygon('coordinates')`
- create a double column without specifying second or third parameters `$blueprint->double('foo')` (but `$blueprint->double('foo', 5, 2)` is supported)
- create a timestamp column with `useCurrent` modifier `$blueprint->timestamp('created_at')->useCurrent()`

### License

Licensed under the [MIT License](https://cheeaun.mit-license.org).

## DEMO

创建web 新应用 webapp
```
laravel new webapp
cd webapp
```

安装 microestc/oscardb
```
composer require microestc/oscardb
```

创建配置文件 config/oscardb.php
```
<?php

return [
    'oscardb' => [
        'driver'    => 'oscar',
        'url'       => env('DB_URL'),
        'host'      => '10.1.1.66',
        'port'      => '2003',
        'database'  => 'osrdb',
        'username'  => 'sysdba',
        'password'  => 'szoscar55',
        'charset'   => 'utf8',
        'prefix'    => '',
        'quoting'   => false,
    ],
];

```
修改默认使用数据库 config/database.php
```
    'default' => 'oscardb',
```

配置默认数据库提供服务 config/app.php
providers 中加入
```
Microestc\OscarDB\OscarDBServiceProvider::class,
```

开始测试

追加路由 routes/web.php 
```
Route::get('/users/index', [UsersController::class, 'index']);

```

app/Http/Controllers/UsersController.php
```
<?php
 
namespace App\Http\Controllers;
 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
 
class UsersController extends Controller
{
    /**
     * Show a list of all of the application's users.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        DB::statement('drop table if exists php_users');

        DB::statement('create table php_users (id int auto_increment primary key, name text)');
        
        DB::unprepared("insert into php_users (name) values ('张三'),('Lili')");

        $users = DB::select('select * from php_users');
        \print_r($users);

        $users = DB::select('select * from php_users where id > ?', [1]);
        \print_r($users);

        $users = DB::connection()->select('select * from php_users');
        \print_r($users);

        $users = DB::connection('oscardb')->select('select * from php_users');
        \print_r($users);

        $id = DB::connection('oscardb')->table('php_users')->insertGetId(
            ['name' => 'john@example.com'], 'id'
        );

        \print_r($id);

        DB::insert('insert into php_users (name) values (?)', ['Marc']);
        $users = DB::select('select * from php_users');
        \print_r($users);


        $affected = DB::update(
            'update php_users set name = ? where id = 1',
            ['Anita']
        );

        \print_r("\n affected:".$affected);

        $deleted = DB::delete('delete from php_users where id = 1');

        \print_r("\n deleted:".$deleted);

        DB::transaction(function () {
            DB::update('update php_users set name = ? where id = 2', ['李四']);
         
            DB::delete('delete from php_posts');
            DB::rollBack();
        }, 2);

        $users = DB::select('select * from php_users where id = 2');
        \print_r($users);
 
        return view('users_index', ['users' => $users]);
    }
}
```

resources/views/users_index.blade.php
```
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

        <style>
            body {
                font-family: 'Nunito', sans-serif;
            }
        </style>
    </head>
    <body class="antialiased">
    </body>
</html>
```

启动应用
```
php artisan serve
```

浏览器键入地址查看结果

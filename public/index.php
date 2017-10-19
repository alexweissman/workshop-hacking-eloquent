<?php
/**
 * Hacking Laravel: Custom Relationships in Eloquent
 *
 * @link      https://github.com/alexweissman/phpworld2017
 * @see       https://world.phparch.com/sessions/hacking-laravel-custom-relationships-in-eloquent/
 * @license   MIT
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Log\MixedFormatter;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Events\Dispatcher;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

$capsule = new Capsule;

$capsule->addConnection([
    'driver'    => 'sqlite',
    'database'  => ':memory:'
]);

$queryEventDispatcher = new Dispatcher(new Container);

$capsule->setEventDispatcher($queryEventDispatcher);

// Make this Capsule instance available globally via static methods
$capsule->setAsGlobal();

// Setup the Eloquent ORM
$capsule->bootEloquent();

// Set up a query logger
$logger = new Logger('query');

$logFile = '../log/queries.log';

$handler = new StreamHandler($logFile);

$formatter = new MixedFormatter(null, null, true);

$handler->setFormatter($formatter);
$logger->pushHandler($handler);

$capsule->connection()->enableQueryLog();

// Register listener to log performed queries
$queryEventDispatcher->listen(QueryExecuted::class, function ($query) use ($logger) {
    $logger->debug("Query executed on database [{$query->connectionName}]:", [
        'query'    => $query->sql,
        'bindings' => $query->bindings,
        'time'     => $query->time . ' ms'
    ]);
});



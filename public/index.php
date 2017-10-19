<?php
/**
 * Hacking Laravel: Custom Relationships in Eloquent
 *
 * @link      https://github.com/alexweissman/phpworld2017
 * @see       https://world.phparch.com/sessions/hacking-laravel-custom-relationships-in-eloquent/
 * @license   MIT
 */

require_once __DIR__ . '/../vendor/autoload.php';

$suite = new PHPUnit_Framework_TestSuite();
$suite->addTestSuite('App\Tests\DatabaseTests');

echo "<pre>";
PHPUnit_TextUI_TestRunner::run($suite);
echo "</pre>";
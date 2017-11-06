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

// Shunt output of PHPUnit to a variable
ob_start();
$runner = new PHPUnit_TextUI_TestRunner;
$runner->doRun($suite, [], false);
$result = ob_get_clean();

echo "<html><head></head><body>";
echo "<pre>";
print_r($result);
echo "</pre>";

echo "<hr>";

echo "<pre>";
$log = App\Tests\DatabaseTests::$arrayHandler->close();

foreach ($log as $record) {
    print_r((string) $record['formatted']);
}
echo "</pre>";
echo "</body></html>";

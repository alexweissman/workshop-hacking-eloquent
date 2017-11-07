<?php
/**
 * Hacking Laravel: Custom Relationships in Eloquent
 *
 * @link      https://github.com/alexweissman/phpworld2017
 * @see       https://world.phparch.com/sessions/hacking-laravel-custom-relationships-in-eloquent/
 * @license   MIT
 */
namespace App\Tests;

use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Diff\Differ;

/**
 * Thrown when an assertion for array equality failed.
 */
class ArrayComparisonFailure extends ComparisonFailure
{
    /**
     * @return string
     */
    public function getDiff()
    {
        if (!$this->actualAsString && !$this->expectedAsString) {
            return '';
        }

        $differ = new Differ("\n--- Expected\n+++ Actual\n");

        $result = "
        <table>
            <tr><th>Expected</th><th>Actual</th><th>Diff</th></tr>
            <tr>
            <td><pre>" . print_r($this->expected, true) . "</pre></td>
            <td><pre>" .
            print_r($this->actual, true) .
            "</pre></td>
            <td><pre>" . $differ->diff($this->expectedAsString, $this->actualAsString) .
            "</pre></td>
            </tr></table>";

        /*
        $result =
            PHP_EOL . PHP_EOL .
            'EXPECTED:' . PHP_EOL .
            '========' . PHP_EOL .
            print_r($this->expected, true) . PHP_EOL . PHP_EOL .
            'YOU GOT:' . PHP_EOL .
            '=======' . PHP_EOL .
            print_r($this->actual, true) . PHP_EOL . PHP_EOL .
            'DIFF:' . PHP_EOL .
            '====' .
            $differ->diff($this->expectedAsString, $this->actualAsString);
        */
        return $result;
    }
}

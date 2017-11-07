<?php
/**
 * Hacking Laravel: Custom Relationships in Eloquent
 *
 * @link      https://github.com/alexweissman/phpworld2017
 * @see       https://world.phparch.com/sessions/hacking-laravel-custom-relationships-in-eloquent/
 * @license   MIT
 */
namespace App\Tests;

use App\Tests\Constraint\ArrayEqual;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{

    /**
     * Asserts that two arrays are equal
     *
     * @param array|ArrayAccess $expected
     * @param array|ArrayAccess $actual
     * @param string $message
     * @param float  $delta
     * @param int    $maxDepth
     * @param bool   $canonicalize
     * @param bool   $ignoreCase
     */
    public static function assertArrayEqual($expected, $actual, $message = '', $delta = 0.0, $maxDepth = 10, $canonicalize = false, $ignoreCase = false)
    {
        if (!(is_array($expected) || $expected instanceof ArrayAccess)) {
            throw \PHPUnit_Util_InvalidArgumentHelper::factory(
                1,
                'array or ArrayAccess'
            );
        }

        if (!(is_array($actual) || $actual instanceof ArrayAccess)) {
            throw \PHPUnit_Util_InvalidArgumentHelper::factory(
                2,
                'array or ArrayAccess'
            );
        }

        $constraint = new ArrayEqual(
            $expected,
            $delta,
            $maxDepth,
            $canonicalize,
            $ignoreCase
        );
        static::assertThat($actual, $constraint, $message);
    }
}

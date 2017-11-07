<?php
/**
 * Hacking Laravel: Custom Relationships in Eloquent
 *
 * @link      https://github.com/alexweissman/phpworld2017
 * @see       https://world.phparch.com/sessions/hacking-laravel-custom-relationships-in-eloquent/
 * @license   MIT
 */
namespace App\Tests\Constraint;

class ArrayEqual extends \PHPUnit_Framework_Constraint_IsEqual
{
    /**
     * Evaluates the constraint for parameter $other
     *
     * If $returnResult is set to false (the default), an exception is thrown
     * in case of a failure. null is returned otherwise.
     *
     * If $returnResult is true, the result of the evaluation is returned as
     * a boolean value instead: true in case of success, false in case of a
     * failure.
     *
     * @param mixed  $other        Value or object to evaluate.
     * @param string $description  Additional information about the test
     * @param bool   $returnResult Whether to return a result or throw an exception
     *
     * @return mixed
     *
     * @throws PHPUnit_Framework_ExpectationFailedException
     */
    public function evaluate($other, $description = '', $returnResult = false)
    {
        // If $this->value and $other are identical, they are also equal.
        // This is the most common path and will allow us to skip
        // initialization of all the comparators.
        if ($this->value === $other) {
            return true;
        }

        $comparatorFactory = \SebastianBergmann\Comparator\Factory::getInstance();

        try {
            $comparator = $comparatorFactory->getComparatorFor(
                $this->value,
                $other
            );

            $comparator->assertEquals(
                $this->value,
                $other,
                $this->delta,
                $this->canonicalize,
                $this->ignoreCase
            );
        } catch (\SebastianBergmann\Comparator\ComparisonFailure $f) {
            if ($returnResult) {
                return false;
            }

            $message = trim($description . "\n" . $f->getMessage());

            throw new \PHPUnit_Framework_ExpectationFailedException(
                $message,
                new \App\Tests\ArrayComparisonFailure(
                    $f->getExpected(),
                    $f->getActual(),
                    $f->getExpectedAsString(),
                    $f->getActualAsString(),
                    false,
                    $f->getMessage()
                )
            );
        }

        return true;
    }
}

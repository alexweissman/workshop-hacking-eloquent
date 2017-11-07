<?php
/**
 * Hacking Laravel: Custom Relationships in Eloquent
 *
 * @link      https://github.com/alexweissman/phpworld2017
 * @see       https://world.phparch.com/sessions/hacking-laravel-custom-relationships-in-eloquent/
 * @license   MIT
 */
namespace App\Tests;

/**
 * Extends PHPUnit_TextUI_ResultPrinter to wrap test results in <pre> for non-CLI contexts.
 */
class Printer extends \PHPUnit_TextUI_ResultPrinter
{
    protected function printHeader()
    {
        $this->writeHTML('<pre>');
        parent::printHeader();
        $this->writeHTML('</pre>');
    }

    /**
     * @param PHPUnit_Framework_TestResult $result
     */
    protected function printFooter(\PHPUnit_Framework_TestResult $result)
    {
        $this->writeHTML('<pre>');
        parent::printFooter($result);
        $this->writeHTML('</pre>');
    }

    protected function printDefect(\PHPUnit_Framework_TestFailure $defect, $count)
    {
        $this->writeHTML('<pre>');
        parent::printDefect($defect, $count);
        $this->writeHTML('</pre>');
    }

    protected function writeHTML($buffer)
    {
        if (PHP_SAPI != 'cli' && PHP_SAPI != 'phpdbg') {
            $this->write($buffer);
        }
    }

    /**
     * @param string $buffer
     */
    public function write($buffer)
    {
        if ($this->out) {
            fwrite($this->out, $buffer);

            if ($this->autoFlush) {
                $this->incrementalFlush();
            }
        } else {
            print $buffer;

            if ($this->autoFlush) {
                $this->incrementalFlush();
            }
        }
    }
}

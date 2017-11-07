<?php
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests;
 
/**
 * Utility class that can print to STDOUT or write to a file.
 */
class Printer extends \PHPUnit_TextUI_ResultPrinter
{

    protected function printHeader()
    {
        $this->write('<pre>');
        parent::printHeader();
        $this->write('</pre>');
    }

    /**
     * @param PHPUnit_Framework_TestFailure $defect
     * @param int                           $count
     */
    protected function printDefect(\PHPUnit_Framework_TestFailure $defect, $count)
    {
        $this->write('<pre>');
        parent::printDefect($defect, $count);
        $this->write('</pre>');
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

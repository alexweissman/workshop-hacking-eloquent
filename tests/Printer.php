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
        parent::printHeader();
    }

    /**
     * @param PHPUnit_Framework_TestFailure $defect
     */
    protected function printDefectTrace(\PHPUnit_Framework_TestFailure $defect)
    {
        $e = $defect->thrownException();
        $this->writeRaw((string) $e);

        while ($e = $e->getPrevious()) {
            $this->write("\nCaused by\n" . $e);
        }
    }

    /**
     * @param string $buffer
     */
    public function write($buffer)
    {
        if (PHP_SAPI != 'cli') {
            $buffer = '<pre>' . $buffer . '</pre>';
        }

        $this->writeRaw($buffer);
    }

    public function writeRaw($buffer)
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

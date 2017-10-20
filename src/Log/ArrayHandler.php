<?php
/**
 * Hacking Laravel: Custom Relationships in Eloquent
 *
 * @link      https://github.com/alexweissman/phpworld2017
 * @see       https://world.phparch.com/sessions/hacking-laravel-custom-relationships-in-eloquent/
 * @license   MIT
 */
namespace App\Log;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

/**
 * Log to an array.
 *
 * @author Sinisa Culic  <sinisaculic@gmail.com>
 */
class ArrayHandler extends AbstractProcessingHandler
{
    protected $log;

    /**
     * @param integer $level  The minimum logging level at which this handler will be triggered
     * @param Boolean $bubble Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct($level = Logger::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return $this->log;
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record)
    {
        $this->log[] = $record;
    }

}

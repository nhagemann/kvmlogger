<?php

namespace KVMLogger;

use Psr\Log\LoggerInterface;

class KVMNullLogger extends KVMLogger implements LoggerInterface
{
    /**
     * Logs with an arbitrary level.
     *
     * @param mixed   $level
     * @param string  $message
     * @param mixed[] $context
     *
     * @return void
     *
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function log($level, $message, array $context = array())
    {
        // do nothing
    }
}

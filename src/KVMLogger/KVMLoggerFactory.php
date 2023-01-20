<?php

namespace KVMLogger;

use Katzgrau\KLogger\Logger;
use Psr\Log\LogLevel;

class KVMLoggerFactory
{
    /**
     * @param string $namespace
     *
     * @return KVMLogger
     */
    public static function create($namespace = 'application')
    {
        return new KVMLogger($namespace);
    }

    /**
     * @param string $realm
     *
     * @return KVMLogger
     */
    public static function createWithKLogger($path, $logLevelThreshold = LogLevel::DEBUG, $realm = 'application', $options = ['filename' => 'kvm.log'])
    {
        $kLogger = new Logger($path, LogLevel::DEBUG, $options);

        $kvmLogger = new KVMLogger($realm);
        ;

        $kvmLogger->addLogger($kLogger, $logLevelThreshold);

        return $kvmLogger;
    }
}

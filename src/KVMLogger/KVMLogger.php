<?php

namespace KVMLogger;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class KVMLogger extends AbstractLogger implements LoggerInterface
{

    /**
     * @var KVMLogger
     */
    private static $instance = null;

    protected $chunk = '';

    protected $namespace = 'application';

    protected $logger = [ ];

    protected $monitor = [ ];

    protected $broadCaster = [ ];

    protected $stopwatch = [ ];

    protected $namespaceThresholds = [ ];

    /**
     * Log Levels
     *
     * @var array
     */
    protected $logLevels = array(
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT     => 1,
        LogLevel::CRITICAL  => 2,
        LogLevel::ERROR     => 3,
        LogLevel::WARNING   => 4,
        LogLevel::NOTICE    => 5,
        LogLevel::INFO      => 6,
        LogLevel::DEBUG     => 7
    );


    public function __construct($namespace = 'application')
    {
        $this->setNamespace($namespace);
        $this->setChunk(substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, 8));

        self::$instance = $this;
    }


    /**
     * @return string
     */
    public function getChunk()
    {
        return $this->chunk;
    }


    /**
     * @param string $chunk
     */
    public function setChunk($chunk)
    {
        $this->chunk = $chunk;
    }


    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }


    /**
     * @param string $namespace
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
    }


    public function setThresholdForNamespace($namespace, $logLevelThreshold = LogLevel::ERROR)
    {
        $this->namespaceThresholds[$namespace] = $this->logLevels[$logLevelThreshold];
    }

//    /**
//     * @return string
//     */
//    public function getRealm()
//    {
//        return $this->realm;
//    }
//
//
//    /**
//     * @param string $realm
//     */
//    public function setRealm($realm)
//    {
//        $this->realm = $realm;
//    }

    public function startStopWatch($event)
    {
        $this->stopwatch[$event] = microtime(true);
    }


    public function logDuration($event, $logLevel = LogLevel::DEBUG)
    {
        $logMessage = new LogMessage();
        $logMessage->setMode('duration');
        $logMessage->addLogValue('event', $event);
        $logMessage->addLogValue('duration', $this->getDuration($event));
        $this->log($logLevel, $logMessage);
    }


    public function getDuration($event = null)
    {
        if ($event != null)
        {
            if (array_key_exists($event, $this->stopwatch))
            {
                $start = $this->stopwatch[$event] - $_SERVER["REQUEST_TIME_FLOAT"];
                $stop  = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];

                $time = $stop - $start;

            }
            else
            {
                return false;
            }
        }
        else
        {
            $time = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
        }
        $time = number_format($time * 1000, 0, '.', '');

        return $time;
    }


    public function createLogMessage($message = '', $logValues = [ ])
    {
        $logMessage = new LogMessage($message);
        $logMessage->setTiming($this->getDuration());

        foreach ($logValues as $k => $v)
        {
            $logMessage->addLogValue($k, $v);
        }

        return $logMessage;
    }


    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     *
     * @return null
     */
    public function log($level, $message, array $context = array())
    {

        if (!$message instanceof LogMessage)
        {
            $message = new LogMessage($message);

        }
        $message->setNamespace($this->getNamespace());
        $message->setChunk($this->getChunk());

        if ($message->getTiming() == '')
        {
            $message->setTiming($this->getDuration());
        }

        if (array_key_exists($level, $this->logLevels))
        {

            if (!array_key_exists($message->getNamespace(), $this->namespaceThresholds) || $this->namespaceThresholds[$message->getNamespace()] >= $this->logLevels[$level])
            {

                foreach ($this->logger as $logger)
                {
                    if ($this->logLevels[$level] >= $this->logLevels[$logger['threshold']])
                    {
                        $logger['logger']->log($level, (string)$message, $context);
                    }
                }
            }
        }
    }


    public function logRequest($logLevel = LogLevel::DEBUG)
    {
        $message = $this->createLogMessage();
        $message->setMode('request');
        if (isset($_SERVER['REQUEST_METHOD']))
        {
            $message->addLogValue('method', $_SERVER['REQUEST_METHOD']);
        }
        if (isset($_SERVER['REQUEST_URI']))
        {
            $message->addLogValue('uri', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        }
        if (isset($_SERVER['QUERY_STRING']))
        {
            $message->addLogValue('query', $_SERVER['QUERY_STRING']);
        }
        $this->log($logLevel, $message);

    }


    public function logResources($level = LogLevel::DEBUG)
    {
        $message = $this->createLogMessage();
        $message->setMode('resource');
        $message->addLogValue('memory', number_format(memory_get_usage(true) / 1048576, 1, '.', ''));
        if (php_sapi_name() == "cli")
        {
            if (isset($_SERVER['SCRIPT_FILENAME']))
            {
                $message->addLogValue('script', $_SERVER['SCRIPT_FILENAME']);
            }
            if (isset($_SERVER['argv']))
            {
                $message->addLogValue('argv', join(' ', $_SERVER['argv']));
            }
        }
        else
        {
            if (isset($_SERVER['REQUEST_URI']))
            {
                $message->addLogValue('uri', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
            }
        }

        $this->log($level, $message);
    }


    public function logBeacon($level = LogLevel::DEBUG)
    {
        $message = $this->createLogMessage();
        $message->setMode('beacon');
        $message->addLogValue('memory', number_format(memory_get_usage(true) / 1048576, 1, '.', ''));
        if (php_sapi_name() == "cli")
        {
            if (isset($_SERVER['SCRIPT_FILENAME']))
            {
                $message->addLogValue('script', $_SERVER['SCRIPT_FILENAME']);
            }
            if (isset($_SERVER['argv']))
            {
                $message->addLogValue('argv', join(' ', $_SERVER['argv']));
            }
        }
        else
        {
            if (isset($_SERVER['REQUEST_URI']))
            {
                $message->addLogValue('uri', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
            }
        }

        $backtrace = debug_backtrace();

        if (isset($backtrace[1]['class']))
        {
            $message->addLogValue('class', $backtrace[1]['class']);
        }

        if (isset($backtrace[1]['function']))
        {
            $message->addLogValue('function', $backtrace[1]['function']);
        }

        if (isset($backtrace[0]['file']))
        {
            $message->addLogValue('file', $backtrace[0]['file']);
        }

        if (isset($backtrace[0]['line']))
        {
            $message->addLogValue('line', $backtrace[0]['line']);
        }

        $this->log($level, $message);
    }


    public function addLogger(LoggerInterface $logger, $logLevelThreshold = LogLevel::DEBUG, $logMonitoringEvents = true, $logBroadcastingEvents = true)
    {
        if (array_key_exists($logLevelThreshold, $this->logLevels))
        {
            $this->logger[] = [ 'logger' => $logger, 'threshold' => $logLevelThreshold, 'monitor' => $logMonitoringEvents, 'broadcast' => $logBroadcastingEvents ];
        }
    }


    public function enablePHPExceptionLogging($level = LogLevel::WARNING, $addContext = false)
    {
        $kvmLogger = $this;

        set_exception_handler(function ($exception) use ($kvmLogger, $level, $addContext)
        {

            $message = $kvmLogger->createLogMessage($exception->getMessage());
            $message->setMode('php');
            $message->addLogValue('type', 'exception');
            $trace = $exception->getTrace();
            $message->addLogValue('exception', get_class($exception));
            $message->addLogValue('code', $exception->getCode());
            if (isset($trace[0]['class']))
            {
                $message->addLogValue('class', $trace[0]['class']);
            }
            if (isset($trace[0]['function']))
            {
                $message->addLogValue('function', $trace[0]['function']);
            }
            $message->addLogValue('file', $trace[0]['file']);
            $message->addLogValue('line', $trace[0]['line']);

            $context = [ ];
            if ($addContext)
            {
                $context = [ 'exception' => $exception ];
            }
            $kvmLogger->log($level, $message, $context);
        });
    }


    public function enablePHPErrorLogging($level = LogLevel::WARNING, $errorTypes = null, $addContext = false)
    {
        $kvmLogger = $this;

        if ($errorTypes == null)
        {
            $errorTypes = E_ALL | E_STRICT;
        }

        set_error_handler(function ($errno, $errstr, $errfile, $errline, array $errcontext) use ($kvmLogger, $level, $addContext)
        {
            $message = $kvmLogger->createLogMessage($errstr);
            $message->setMode('php');
            $message->addLogValue('type', 'error');
            $message->addLogValue('code', $errno);

            $message->addLogValue('file', $errfile);
            $message->addLogValue('line', $errline);

            $context = [ ];
            if ($addContext)
            {
                $context = [ 'context' => $errcontext ];
            }
            $kvmLogger->log($level, $message, $context);
        }, $errorTypes);
    }


    public function logValue($realm, $type, $subtype = null, $value, $message = '')
    {
        if (!$message instanceof LogMessage)
        {
            $message = new LogMessage($message);

        }
        $message->setMode('value');
        $message->setNamespace($this->getNamespace());
        $message->setChunk($this->getChunk());

        if ($message->getTiming() == '')
        {
            $message->setTiming($this->getDuration());
        }

        $message->setRealm($realm);
        $message->setType($type);
        $message->setSubtype($subtype);
        $message->addLogValue('value', $value);

        foreach ($this->monitor as $monitor)
        {
            $monitor->saveValue($message);
        }
        foreach ($this->logger as $logger)
        {
            if ($logger['monitor'] == true)
            {
                $logger['logger']->log('info', (string)$message);
            }
        }
    }


    public function logCounter($realm, $type, $subtype = null, $message = '')
    {
        if (!$message instanceof LogMessage)
        {
            $message = new LogMessage($message);

        }
        $message->setMode('counter');
        $message->setNamespace($this->getNamespace());
        $message->setChunk($this->getChunk());

        if ($message->getTiming() == '')
        {
            $message->setTiming($this->getDuration());
        }

        $message->setRealm($realm);
        $message->setType($type);
        $message->setSubtype($subtype);

        foreach ($this->monitor as $monitor)
        {
            $monitor->count($message);
        }
        foreach ($this->logger as $logger)
        {
            if ($logger['monitor'] == true)
            {
                $logger['logger']->log('info', (string)$message);
            }
        }
    }


    public function addMonitor($monitor)
    {
        $this->monitor[] = $monitor;
    }


    public function broadcast($realm, $type, $subtype = null, $message = '')
    {
        if (!$message instanceof LogMessage)
        {
            $message = new LogMessage($message);

        }
        $message->setMode('broadcast');
        $message->setNamespace($this->getNamespace());
        $message->setChunk($this->getChunk());

        if ($message->getTiming() == '')
        {
            $message->setTiming($this->getDuration());
        }

        $message->setRealm($realm);
        $message->setType($type);
        $message->setSubtype($subtype);

        foreach ($this->broadCaster as $broadCaster)
        {
            $broadCaster->broadcast($message);
        }
        foreach ($this->logger as $logger)
        {
            if ($logger['broadcast'] == true)
            {
                $logger['logger']->log('info', (string)$message);
            }
        }
    }


    public function addBroadcaster($broadCaster)
    {
        $this->broadCaster[] = $broadCaster;
    }


    /**
     * @param string $namespace
     *
     * @return KVMLogger
     */
    public static function instance($namespace = 'application')
    {
        if (!self::$instance)
        {
            self::$instance = new KVMNullLogger();
        }

        $kvmLogger = self::$instance;
        $kvmLogger->setNamespace($namespace);

        return $kvmLogger;
    }

}
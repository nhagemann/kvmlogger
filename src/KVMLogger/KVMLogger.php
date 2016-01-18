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

    protected $stopwatch = [ ];

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


    /**
     * @return string
     */
    public function getRealm()
    {
        return $this->realm;
    }


    /**
     * @param string $realm
     */
    public function setRealm($realm)
    {
        $this->realm = $realm;
    }


    public function startStopWatch($event)
    {
        $this->stopwatch[$event] = microtime(true);
    }


    public function logDuration($event, $logLevel = LogLevel::DEBUG)
    {
        $logMessage = new LogMessage();
        $logMessage->setMode('stp');
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

            foreach ($this->logger as $logger)
            {
                if ($this->logLevels[$level] <= $this->logLevels[$logger['threshold']])
                {
                    $logger['logger']->log($level, $message, $context);
                }
            }
        }
    }


    public function logRequest($logLevel = LogLevel::DEBUG)
    {
        $message = $this->createLogMessage();
        $message->setMode('req');
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
        $message->setMode('res');
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


    public function addLogger(LoggerInterface $logger, $logLevelThreshold = LogLevel::DEBUG, $logMonitoringEvents = true)
    {
        if (array_key_exists($logLevelThreshold, $this->logLevels))
        {
            $this->logger[] = [ 'logger' => $logger, 'threshold' => $logLevelThreshold, 'monitor' => $logMonitoringEvents ];
        }
    }


    public function addMonitor()
    {

    }


    public function enablePHPExceptionLogging($level = LogLevel::DEBUG, $addContext = false)
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


    public function enablePHPErrorLogging($level = LogLevel::DEBUG, $errorTypes = null, $addContext = false)
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
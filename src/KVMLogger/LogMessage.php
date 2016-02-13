<?php

namespace KVMLogger;

class LogMessage
{

    protected $mode = 'message';

    protected $chunk = '';

    protected $namespace = 'application';

    protected $realm = '';

    protected $type = '';

    protected $subtype = '';

    protected $message = '';

    protected $dateTime = '';

    protected $timing;

    protected $additionalLogValues = [ ];


    public function __construct($message = '')
    {
        $this->dateTime = new \DateTime();
        $this->setMessage($message);
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
    public function getMode()
    {
        return $this->mode;
    }


    /**
     * @param string $method
     */
    public function setMode($method)
    {
        $this->mode = $method;
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
     * @return mixed
     */
    public function getRealm()
    {
        return $this->realm;
    }


    /**
     * @param mixed $realm
     */
    public function setRealm($realm)
    {
        $this->realm = $realm;
    }


    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }


    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }


    /**
     * @return mixed
     */
    public function getSubtype()
    {
        return $this->subtype;
    }


    /**
     * @param mixed $subtype
     */
    public function setSubtype($subtype)
    {
        $this->subtype = $subtype;
    }


    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }


    /**
     * @param mixed $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }


    /**
     * @return mixed
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }


    /**
     * @param mixed $dateTime
     */
    public function setDateTime($dateTime)
    {
        $this->dateTime = $dateTime;
    }


    /**
     * @return mixed
     */
    public function getTiming()
    {
        return $this->timing;
    }


    /**
     * @param mixed $timing
     */
    public function setTiming($timing)
    {
        $this->timing = $timing;
    }


    public function addLogValue($key, $value)
    {
        $this->additionalLogValues[$key] = $value;
    }


    public function __tostring()
    {
        $logValues            = [ ];
        $logValues['ms']      = $this->getTiming();
        $logValues['mode']    = $this->getMode();
        $logValues['message'] = $this->getMessage();

        $logValues['realm'] = $this->getRealm();
        $logValues['type']    = $this->getType();
        $logValues['subtype'] = $this->getSubtype();

        $logValues = array_merge($logValues, $this->additionalLogValues);

        $logValues['namespace'] = $this->getNamespace();
        $logValues['chunk'] = $this->getChunk();

        $message = [ ];
        foreach ($logValues as $k => $v)
        {
            if ($v !== '')
            {
                $message[] = $k . '="' . str_replace('"', '\"', $v) . '"';
            }
        }

        return join(', ', $message);

    }
}
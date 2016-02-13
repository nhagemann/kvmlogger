<?php

namespace KVMLogger\Monitor;

use KVMLogger\LogMessage;
use Symfony\Component\Yaml\Yaml;

class YAMLMonitor
{

    protected $filename;

    protected $data = [ ];


    public function __construct($filename)
    {
        $this->filename = $filename;

    }


    protected function getYAML()
    {
        if (!$this->data)
        {
            if (file_exists($this->filename))
            {
                $yaml       = new Yaml();
                $this->data = $yaml->parse(file_get_contents($this->filename));
            }
        }

        return $this->data;
    }


    protected function saveYAML($data)
    {
        $yaml = new Yaml();
        file_put_contents($this->filename, $yaml->dump($data, 4, 2));
        $this->data = $data;
    }


    public function count(LogMessage $message)
    {
        $c = 1;

        $data = $this->getYAML();

        if ($message->getSubtype())
        {
            if (isset($data['Counter'][$message->getRealm()][$message->getType()][$message->getSubtype()]))
            {
                $c = $data['Counter'][$message->getRealm()][$message->getType()][$message->getSubtype()] + 1;
            }
            $data['Counter'][$message->getRealm()][$message->getType()][$message->getSubtype()] = $c;
        }
        else
        {
            if (isset($data['Counter'][$message->getRealm()][$message->getType()]))
            {
                $c = $data['Counter'][$message->getRealm()][$message->getType()] + 1;
            }
            $data['Counter'][$message->getRealm()][$message->getType()] = $c;
        }

        $this->saveYAML($data);

        return $c;
    }


    public function saveValue(LogMessage $message)
    {
        $data = $this->getYAML();

        if ($message->getSubtype())
        {
            $data['Values'][$message->getRealm()][$message->getType()][$message->getSubtype()] = $message->getLogValue('value');
        }
        else
        {
            $data['Values'][$message->getRealm()][$message->getType()] = $message->getLogValue('value');
        }

        $this->saveYAML($data);
    }
}

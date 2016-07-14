<?php

namespace KVMLogger;

interface MonitorInterface
{
    public function count(LogMessage $message);

    public function saveValue(LogMessage $message);
}
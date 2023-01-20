<?php

namespace KVMLogger\Broadcaster;

use KVMLogger\LogMessage;

class EmailBroadcaster
{
    protected $name;

    protected $email;

    protected $headers;

    public function __construct($email, $name = '', $headers = [ ])
    {
        $this->name    = $name;
        $this->email   = $email;
        $this->headers = $headers;
    }

    public function broadcast(LogMessage $message)
    {
        $to = $this->name . ' <' . $this->email . '>';

        $headers   = $this->headers;
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-type: text/plain; charset=utf-8";
        $headers[] = "X-Mailer: PHP/" . phpversion();

        $subject = 'New Broadcast Event: ' . $message->getRealm() . ' - ' . $message->getType();
        if ($message->getSubtype()) {
            $subject .= ' - ' . $message->getSubtype();
        }

        $text = 'Namespace: ' . $message->getNamespace() . PHP_EOL;
        $text .= 'Chunk:     ' . $message->getChunk() . PHP_EOL . PHP_EOL;
        $text .= 'Realm:     ' . $message->getRealm() . PHP_EOL;
        $text .= 'Type:      ' . $message->getType() . PHP_EOL;
        $text .= 'Subtype:   ' . $message->getSubtype() . PHP_EOL . PHP_EOL . PHP_EOL;
        $text .= $message->getMessage();

        mail($to, $subject, $text, implode("\r\n", $headers));
    }
}

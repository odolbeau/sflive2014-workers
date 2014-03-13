<?php

namespace SensioLabs\Bundle\Sflive2014Bundle\Swiftmailer\Spool;

use SensioLabs\Bundle\Sflive2014Bundle\Amqp\Broker;

class AmqpSpool implements \Swift_Spool
{
    private $broker;

    public function __construct(Broker $broker)
    {
        $this->broker = $broker;
    }

    public function start()
    {
        $this->broker->connect();
    }

    public function stop()
    {
        $this->broker->disconnect();
    }

    public function isStarted()
    {
        return $this->broker->isConnected();
    }

    public function queueMessage(\Swift_Mime_Message $message)
    {
        if (!$this->isStarted()) {
            $this->start();
        }

        $this->broker->queueMail(json_encode(array('mail' => base64_encode(serialize($message)))));
    }

    public function flushQueue(\Swift_Transport $transport, &$failedRecipients = null)
    {
        throw new \BadMethodCallException('Use a consumer to flush queues.');
    }
}

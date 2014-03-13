<?php

namespace SensioLabs\Bundle\Sflive2014Bundle\Amqp;

class Broker
{
    protected $conn;
    protected $exchanges;
    protected $queues;
    protected $channel;

    public function __construct($user = 'guest', $password = '' , $host = 'localhost', $port = 5672, $vhost = '/')
    {
        $this->conn = new \AMQPConnection(array(
            'host' => $host,
            'vhost' => $vhost,
            'port' => $port,
            'login' => $user,
            'password' => $password,
        ));
        $this->exchanges = array();
        $this->queues = array();
    }

    public function consumeMail()
    {
        return $this->queues['mail']->get();
    }

    public function ackMail(\AMQPEnvelope $msg)
    {
        $this->queues['mail']->ack($msg->getDeliveryTag());
    }

    public function queueMail($message)
    {
        $this->exchanges['mail']->publish($message, 'mail', \AMQP_MANDATORY, array('delivery_mode' => 2));
    }

    public function isConnected()
    {
        return $this->conn->isConnected();
    }

    public function disconnect()
    {
        if ($this->conn->isConnected()) {
            $this->conn->disconnect();
        }
    }

    public function connect()
    {
        if ($this->conn->isConnected()) {
            return;
        }

        $this->conn->reconnect();

        $this->channel = new \AMQPChannel($this->conn);

        $exchanges = array('mail',);

        // Exchanges
        foreach ($exchanges as $name) {
            $this->exchanges[$name] = $this->createExchange($name);
        }

        // Queues
        foreach ($exchanges as $name) {
            $this->queues[$name] = $this->createQueue($name);
            $this->queues[$name]->bind($name, $name);
        }
    }

    protected function createExchange($name)
    {
        if (!$this->conn->isConnected()) {
            throw new LogicException('Can not create exchange if not connected.');
        }

        $exchange = new \AMQPExchange($this->channel);
        $exchange->setName($name);
        $exchange->setType(\AMQP_EX_TYPE_DIRECT);
        $exchange->setFlags(\AMQP_DURABLE);
        $exchange->declareExchange();

        return $exchange;
    }

    protected function createQueue($name, array $arguments = array())
    {
        if (!$this->conn->isConnected()) {
            throw new LogicException('Can not create queue if not connected.');
        }

        $queue = new \AMQPQueue($this->channel);
        $queue->setName($name);
        $queue->setFlags(\AMQP_DURABLE);
        if ($arguments) {
            $queue->setArguments($arguments);
        }
        $queue->declareQueue();

        return $queue;
    }
}

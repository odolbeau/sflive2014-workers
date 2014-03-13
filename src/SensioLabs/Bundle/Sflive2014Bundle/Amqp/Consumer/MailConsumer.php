<?php

namespace SensioLabs\Bundle\Sflive2014Bundle\Amqp\Consumer;

if (!defined('JSON_PRETTY_PRINT')) {
    define('JSON_PRETTY_PRINT', 128);
}

use Psr\Log\LoggerInterface;
use SensioLabs\Bundle\Sflive2014Bundle\Amqp\Broker;

class MailConsumer implements ConsumerInterface
{
    private $mailer;
    private $realTransport;
    private $broker;
    private $logger;
    private $stopped;
    private $time;

    public function __construct(\Swift_Transport $realTransport, Broker $broker = null, LoggerInterface $logger = null)
    {
        $this->realTransport = $realTransport;
        $this->broker = $broker ?: new Broker();
        $this->logger = $logger;
        $this->stopped = false;
        $this->time = time();
    }

    public function run()
    {
        try {
            $this->broker->connect();

            $this->logger and $this->logger->info('Mail consumer started.');

            while (true) {
                pcntl_signal_dispatch();

                if ($this->stopped) {
                    $this->stopTransport();

                    return;
                }

                $this->logMemoryUsage();

                $first = true;

                while (false !== $msg = $this->broker->consumeMail()) {
                    if ($first) {
                        $this->startTransport();
                        $first = false;
                    }

                    $this->consumeMail($msg);

                    pcntl_signal_dispatch();
                    if ($this->stopped) {
                        $this->stopTransport();

                        return;
                    }

                    $this->logMemoryUsage();
                }

                if ($this->realTransport->isStarted()) {
                    $this->realTransport->stop();
                }

                usleep(200000);
            }
        } catch (\AMQPConnectionException $e) {
            $this->logger and $this->logger->error(sprintf('AMQP is down (%s).', $e->getMessage(), array('exception' => $e)));

            $this->stop();
            $this->stopTransport();

            return;
        }
    }

    public function stop()
    {
        $this->logger and $this->logger->info('Mail consumer stopped.');

        $this->stopped = true;
    }

    public function consumeMail(\AMQPEnvelope $msg)
    {
        $this->logger and $this->logger->debug('Receive a new mail.');

        $payload = json_decode($msg->getBody(), true);

        $mail = unserialize(base64_decode($payload['mail']));

        try {
            $this->realTransport->send($mail);
        } catch (\Exception $e) {
            $this->logger and $this->logger->critical(sprintf('Impossible to send mail.'), array('exception' => $e, 'payload' => $payload));
        }

        $this->broker->ackMail($msg);
    }

    private function startTransport()
    {
        if ($this->realTransport->isStarted()) {
            $this->logger and $this->logger->debug('Stoping mail transport.');
            $this->realTransport->stop();
            $this->logger and $this->logger->debug('Mail transport stopped.');
        }

        $this->logger and $this->logger->debug('Starting mail transport.');
        $this->realTransport->start();
        $this->logger and $this->logger->debug('Mail transport stared.');
    }

    private function stopTransport()
    {
        if ($this->realTransport->isStarted()) {
            $this->logger and $this->logger->debug('Stoping mail transport.');
            $this->realTransport->stop();
            $this->logger and $this->logger->debug('Mail transport stopped.');
        }
    }

    private function logMemoryUsage()
    {
        if (time() >= $this->time + 10) {
            $this->time = time();
            $this->logger and $this->logger->debug(sprintf('Memory: %.2f Mb', memory_get_usage() / (1024 * 1024)));
        }
    }
}

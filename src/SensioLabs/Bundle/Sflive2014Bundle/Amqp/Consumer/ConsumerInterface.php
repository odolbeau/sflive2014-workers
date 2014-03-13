<?php

namespace SensioLabs\Bundle\Sflive2014Bundle\Amqp\Consumer;

interface ConsumerInterface
{
    public function run();

    public function stop();
}

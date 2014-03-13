<?php

namespace SensioLabs\Bundle\Sflive2014Bundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumerCommand extends ContainerAwareCommand
{
    private static $consumers = array(
        'mail',
    );

    protected function configure()
    {
        $this
            ->setName('project:consumer:run')
            ->setDescription('Run a rabbitmq consumer.')
            ->setDefinition(array(
                new InputArgument('daemon', InputArgument::REQUIRED, 'The daemon'),
            ))
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $processName = sprintf('connect_%s', $input->getArgument('daemon'));

        if (function_exists('cli_set_process_title')) {
            cli_set_process_title($processName);
        } elseif (function_exists('setproctitle')) {
            setproctitle($processName);
        } else {
            $output->writeln('<comment>Install the proctitle pecl to be able to changed the process title.</comment>');
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $service = sprintf('sflive.consumer.%s', $input->getArgument('daemon'));

        if (!$this->getContainer()->has($service)) {
            throw new \InvalidArgumentException(sprintf('the service %s does not exist. Available ones are: "%s".', $service, implode('", "', self::$consumers)));
        }

        $daemon = $this->getContainer()->get($service);

        pcntl_signal(SIGTERM, function () use ($daemon) {
            $daemon->stop();
        });
        pcntl_signal(SIGINT, function () use ($daemon) {
            $daemon->stop();
        });

        $daemon->run();
    }
}

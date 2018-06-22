<?php

/**
 * @file
 * Contains \WP\Console\Core\EventSubscriber\CallCommandListener.
 */

namespace WP\Console\Core\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Command\Command;
use WP\Console\Core\Utils\ChainQueue;
use WP\Console\Core\Style\WPStyle;

/**
 * Class CallCommandListener
 *
 * @package WP\Console\Core\EventSubscriber
 */
class CallCommandListener implements EventSubscriberInterface
{
    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * CallCommandListener constructor.
     *
     * @param ChainQueue $chainQueue
     */
    public function __construct(ChainQueue $chainQueue)
    {
        $this->chainQueue = $chainQueue;
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function callCommands(ConsoleTerminateEvent $event)
    {
        $command = $event->getCommand();

        /* @var WPStyle $io */
        $io = new WPStyle($event->getInput(), $event->getOutput());

        if (!$command instanceof Command) {
            return;
        }

        $application = $command->getApplication();
        $commands = $this->chainQueue->getCommands();

        if (!$commands) {
            return 0;
        }

        foreach ($commands as $chainedCommand) {
            $callCommand = $application->find($chainedCommand['name']);

            if (!$callCommand) {
                continue;
            }

            $input = new ArrayInput($chainedCommand['inputs']);
            if (!is_null($chainedCommand['interactive'])) {
                $input->setInteractive($chainedCommand['interactive']);
            }

            $io->text($chainedCommand['name']);
            $allowFailure = array_key_exists('allow_failure', $chainedCommand) ? $chainedCommand['allow_failure'] : false;
            try {
                $callCommand->run($input, $io);
            } catch (\Exception $e) {
                if (!$allowFailure) {
                    $io->error($e->getMessage());
                    return 1;
                }
            }
        }
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::TERMINATE => 'callCommands'];
    }
}

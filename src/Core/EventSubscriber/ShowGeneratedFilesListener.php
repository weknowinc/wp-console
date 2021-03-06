<?php

/**
 * @file
 * Contains \WP\Console\Core\EventSubscriber\ShowGeneratedFilesListener.
 */

namespace WP\Console\Core\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Console\Command\Command;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Core\Utils\FileQueue;
use WP\Console\Core\Utils\ShowFile;

/**
 * Class ShowGeneratedFilesListener
 *
 * @package WP\Console\Core\EventSubscriber
 */
class ShowGeneratedFilesListener implements EventSubscriberInterface
{
    /**
     * @var FileQueue
     */
    protected $fileQueue;

    /**
     * @var ShowFile
     */
    protected $showFile;

    /**
     * ShowGeneratedFilesListener constructor.
     *
     * @param FileQueue $fileQueue
     * @param ShowFile  $showFile
     */
    public function __construct(FileQueue $fileQueue, ShowFile $showFile)
    {
        $this->fileQueue = $fileQueue;
        $this->showFile = $showFile;
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function showGeneratedFiles(ConsoleTerminateEvent $event)
    {
        /* @var Command $command */
        $command = $event->getCommand();
        /* @var WPStyle $io */
        $io = new WPStyle($event->getInput(), $event->getOutput());

        if ($event->getExitCode() != 0) {
            return;
        }

        if ('self-update' == $command->getName()) {
            return;
        }

        $files = $this->fileQueue->getFiles();
        if ($files) {
            $this->showFile->generatedFiles($io, $files, false);
        }
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::TERMINATE => 'showGeneratedFiles'];
    }
}

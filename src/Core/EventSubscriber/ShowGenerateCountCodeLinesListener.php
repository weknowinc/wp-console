<?php

/**
 * @file
 * Contains \WP\Console\Core\EventSubscriber\ShowGenerateCountCodeLinesListener.
 */

namespace WP\Console\Core\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use WP\Console\Core\Utils\TranslatorManager;
use WP\Console\Core\Utils\CountCodeLines;
use WP\Console\Core\Style\WPStyle;

/**
 * Class ShowGenerateCountCodeLinesListener
 *
 * @package WP\Console\Core\EventSubscriber
 */
class ShowGenerateCountCodeLinesListener implements EventSubscriberInterface
{

    /**
     * @var ShowGenerateChainListener
     */
    protected $countCodeLines;

    /**
     * @var TranslatorManager
     */
    protected $translator;

    /**
     * ShowGenerateChainListener constructor.
     *
     * @param TranslatorManager $translator
     *
     * @param CountCodeLines $countCodeLines
     *
     */
    public function __construct(
        TranslatorManager $translator,
        CountCodeLines $countCodeLines
    ) {
        $this->translator = $translator;
        $this->countCodeLines = $countCodeLines;
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function showGenerateCountCodeLines(ConsoleTerminateEvent $event)
    {
        if ($event->getExitCode() != 0) {
            return;
        }

        /* @var WPStyle $io */
        $io = new WPStyle($event->getInput(), $event->getOutput());

        $countCodeLines = $this->countCodeLines->getCountCodeLines();
        if ($countCodeLines > 0) {
            $io->commentBlock(
                sprintf(
                    $this->translator->trans('application.messages.lines-code'),
                    $countCodeLines
                )
            );
        }
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::TERMINATE => 'showGenerateCountCodeLines'];
    }
}

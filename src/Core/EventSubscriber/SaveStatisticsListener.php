<?php

/**
 * @file
 * Contains \WP\Console\Core\EventSubscriber\SaveStatisticsListener.
 */

namespace WP\Console\Core\EventSubscriber;

use WP\Console\Core\Command\Chain\ChainCustomCommand;
use WP\Console\Core\Utils\ConfigurationManager;
use WP\Console\Core\Utils\CountCodeLines;
use WP\Console\Core\Utils\TranslatorManager;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class SaveStatisticsListener
 *
 * @package WP\Console\Core\EventSubscriber
 */
class SaveStatisticsListener implements EventSubscriberInterface
{

    /**
     * @var ShowGenerateChainListener
     */
    protected $countCodeLines;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var TranslatorManager
     */
    protected $translator;

    /**
     * FileSystem $fs
     */
    protected $fs;

    /**
     * SaveStatisticsListener constructor.
     *
     * @param CountCodeLines             $countCodeLines
     * @param ConfigurationManager       $configurationManager
     * @param TranslatorManager $translator
     */
    public function __construct(
        CountCodeLines $countCodeLines,
        ConfigurationManager $configurationManager,
        TranslatorManager $translator
    ) {
        $this->countCodeLines = $countCodeLines;
        $this->configurationManager = $configurationManager;
        $this->translator = $translator;

        $this->fs = new Filesystem();
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function saveStatistics(ConsoleTerminateEvent $event)
    {
        if ($event->getExitCode() != 0) {
            return;
        }

        $globalConfig = $this->configurationManager->getConfigAsArray();

        //Validate if the config is enable.
        if (is_null($globalConfig) || !$globalConfig['application']['share']['statistics']) {
            return;
        }

        //Check that the namespace starts with 'WP\Console'.
        $class = new \ReflectionClass($event->getCommand());
        if (strpos($class->getNamespaceName(), "WP\Console") !== 0) {
            return;
        }

        //Validate if the command is not a custom chain command.
        if ($event->getCommand() instanceof ChainCustomCommand) {
            return;
        }

        $path =  $path = sprintf(
            '%s/.wp-console/stats/',
            $this->configurationManager->getHomeDirectory()
        );

        $information = $event->getCommand()->getName() . ';' . $this->translator->getLanguage();

        $countCodeLines = $this->countCodeLines->getCountCodeLines();
        if ($countCodeLines > 0) {
            $information = $information . ';' . $countCodeLines;
        }


        $this->fs->appendToFile(
            $path .  date('Y-m-d') . '-pending.csv',
            $information . PHP_EOL
        );
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::TERMINATE => 'saveStatistics'];
    }
}

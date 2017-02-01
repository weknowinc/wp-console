<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Module\DebugCommand.
 */

namespace WP\Console\Command\Plugin;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use WP\Console\Command\Shared\CommandTrait;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Utils\Site;
use WP\Console\Extension\Manager;

class DebugCommand extends Command
{
    use CommandTrait;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var Site
     */
    protected $site;


    /**
     * DebugCommand constructor.
     *
     * @param Manager $extensionManager
     * @param Site                 $site
     */
    public function __construct(
        Manager $extensionManager,
        Site $site
    ) {
        $this->extensionManager = $extensionManager;
        $this->site = $site;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('plugin:debug')
            ->setDescription($this->trans('commands.plugin.debug.description'))
            ->addOption(
                'status',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.plugin.debug.options.status')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);

        $status = $input->getOption('status');

        $tableHeader = [
            $this->trans('commands.plugin.debug.messages.name'),
            $this->trans('commands.plugin.debug.messages.status'),
            $this->trans('commands.plugin.debug.messages.plugin-uri'),
            $this->trans('commands.plugin.debug.messages.version'),
            $this->trans('commands.plugin.debug.messages.author'),
            $this->trans('commands.plugin.debug.messages.author-url')
        ];

        $tableRows = [];

        $discoverPlugins = $this->extensionManager->discoverPlugins();
        if($status == 'disabled') {
            $discoverPlugins->showDeactivated();
        } elseif($status == 'enabled') {
            $discoverPlugins->showActivated();
        } else {
            $discoverPlugins->showActivated()->showDeactivated();
        }

        $plugins = $discoverPlugins->getList();


        foreach ($plugins as $plugin => $pluginData ) {
            $pluginStatus = ($this->site->isPluginActive($plugin))?$this->trans('commands.common.status.enabled'): $this->trans('commands.common.status.disabled');


            $tableRows [] = [
                $pluginData['Name'],
                $pluginStatus,
                $pluginData['PluginURI'],
                $pluginData['Version'],
                $pluginData['AuthorName'],
                $pluginData['AuthorURI']
            ];
        }

        $io->table($tableHeader, $tableRows, 'compact');
    }
}

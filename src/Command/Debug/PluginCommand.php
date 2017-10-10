<?php

/**
 * @file
 * Contains \WP\Console\Command\Debug\PluginCommand.
 */

namespace WP\Console\Command\Debug;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WP\Console\Core\Command\Command;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Utils\Site;
use WP\Console\Extension\Manager;

class PluginCommand extends Command
{

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
     * @param Site    $site
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
            ->setName('debug:plugin')
            ->setDescription($this->trans('commands.debug.plugin.description'))
            ->addOption(
                'status',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.debug.plugin.options.status')
            )
            ->setAliases(['dp']);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);

        $status = $input->getOption('status');

        $tableHeader = [
            $this->trans('commands.debug.plugin.messages.name'),
            $this->trans('commands.debug.plugin.messages.status'),
            $this->trans('commands.debug.plugin.messages.plugin-uri'),
            $this->trans('commands.debug.plugin.messages.version'),
            $this->trans('commands.debug.plugin.messages.author'),
            $this->trans('commands.debug.plugin.messages.author-url')
        ];

        $tableRows = [];

        $discoverPlugins = $this->extensionManager->discoverPlugins();
        if ($status == 'disabled') {
            $discoverPlugins->showDeactivated();
        } elseif ($status == 'enabled') {
            $discoverPlugins->showActivated();
        } else {
            $discoverPlugins->showActivated()->showDeactivated();
        }

        $plugins = $discoverPlugins->getList();


        foreach ($plugins as $plugin => $pluginData) {
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

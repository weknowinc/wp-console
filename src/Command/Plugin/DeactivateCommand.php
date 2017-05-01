<?php

/**
 * @file
 * Contains \WP\Console\Command\Plugin\DectivateCommand.
 */

namespace WP\Console\Command\Plugin;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use WP\Console\Command\Shared\CommandTrait;
use WP\Console\Command\Shared\PluginTrait;
use WP\Console\Utils\Site;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Utils\Validator;
use WP\Console\Extension\Manager;
use WP\Console\Core\Utils\ChainQueue;

/**
 * Class DeactivateCommand
 *
 * @package WP\Console\Command\Plugin
 */
class DeactivateCommand extends Command
{
    use CommandTrait;
    use pluginTrait;

    /**
     * @var Site
     */
    protected $site;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var string
     */
    protected $appRoot;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * InstallCommand constructor.
     *
     * @param Site       $site
     * @param Validator  $validator
     * @param Manager    $extensionManager
     * @param $appRoot
     * @param ChainQueue $chainQueue
     */
    public function __construct(
        Site $site,
        Validator $validator,
        Manager $extensionManager,
        $appRoot,
        ChainQueue $chainQueue
    ) {
        $this->site = $site;
        $this->validator = $validator;
        $this->extensionManager = $extensionManager;
        $this->appRoot = $appRoot;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('plugin:deactivate')
            ->setDescription($this->trans('commands.plugin.deactivate.description'))
            ->addArgument(
                'plugin',
                InputArgument::IS_ARRAY,
                $this->trans('commands.plugin.deactivate.arguments.plugin')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);

        $plugin = $input->getArgument('plugin');
        if (!$plugin) {
            $plugin = $this->pluginQuestion($io, false);
            $input->setArgument('plugin', $plugin);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);

        $plugin = $input->getArgument('plugin');

        if (!is_array($plugin)) {
            $plugins = [$plugin];
        } else {
            $plugins = $plugin;
        }

        try {
            $extensions = $this->extensionManager->discoverPlugins()->showActivated()->getList();
            $extensions = array_combine(array_keys($extensions), array_column($extensions, 'Name'));

            foreach ($plugins as $plugin) {
                $pluginFile = array_search($plugin, $extensions);
                print $pluginFile . PHP_EOL;
                $this->site->deactivatePlugins($pluginFile);
            }

            $io->success(
                sprintf(
                    $this->trans('commands.plugin.deactivate.messages.success'),
                    implode(', ', $plugins)
                )
            );
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return 1;
        }
    }
}

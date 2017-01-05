<?php

/**
 * @file
 * Contains \WP\Console\Core\Command\Chain\ChainDebugCommand.
 */

namespace WP\Console\Core\Command\Chain;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use WP\Console\Command\Shared\CommandTrait;
use WP\Console\Core\Utils\ChainDiscovery;
use WP\Console\Core\Style\WPStyle;
/**
 * Class ChainDebugCommand
 * @package Drupal\Console\Core\Command\Chain
 */
class ChainDebugCommand extends Command
{
    use CommandTrait;

    /**
     * @var ChainDiscovery
     */
    protected $chainDiscovery;

    /**
     * ChainDebugCommand constructor.
     *
     * @param ChainDiscovery $chainDiscovery
     */
    public function __construct(
        ChainDiscovery $chainDiscovery
    ) {
        $this->chainDiscovery = $chainDiscovery;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('chain:debug')
            ->setDescription($this->trans('commands.chain.debug.description'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);
        $files = $this->chainDiscovery->getChainFiles();

        foreach ($files as $directory => $chainFiles) {
            $io->info($this->trans('commands.chain.debug.messages.directory'), false);
            $io->comment($directory);

            $tableHeader = [
                $this->trans('commands.chain.debug.messages.file')
            ];

            $tableRows = [];
            foreach ($chainFiles as $file) {
                $tableRows[] = $file;
            }

            $io->table($tableHeader, $tableRows);
        }

        return 0;
    }
}

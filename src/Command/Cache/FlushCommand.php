<?php

/**
 * @file
 * Contains \WP\Console\Command\Cache\FlushCommand.
 */

namespace WP\Console\Command\Cache;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use WP\Console\Command\Shared\CommandTrait;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Utils\Site;
use WP\Console\Extension\Manager;

class FlushCommand extends Command
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
     * @param Site $site
     */
    public function __construct(
        Site $site
    ) {
        $this->site = $site;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('cache:flush')
            ->setDescription($this->trans('commands.cache.flush.description'))
            ->setAliases(['cf']);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);

        $success = $this->site->cacheFlush();

        if ($success) {
            $io->info($this->trans('commands.cache.flush.messages.successful'));
        } else {
            $io->error($this->trans('commands.cache.flush.messages.fail'));
        }
    }
}

<?php

/**
 * @file
 * Contains \WP\Console\Command\Debug\MultisiteCommand.
 */

namespace WP\Console\Command\Debug;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WP\Console\Core\Command\Command;
use WP\Console\Utils\Site;

/**
 * Class SiteDebugCommand
 *
 * @package WP\Console\Command\Site
 */
class MultisiteCommand extends Command
{
    protected $appRoot;

    /**
     * @var Site
     */
    protected $site;

    /**
     * DebugCommand constructor.
     *
     * @param $appRoot
     * @param Site    $site
     */
    public function __construct(
        $appRoot,
        Site $site
    ) {
        $this->appRoot = $appRoot;
        $this->site = $site;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('debug:multisite')
            ->setDescription($this->trans('commands.debug.multisite.description'))
            ->setHelp($this->trans('commands.debug.multisite.help'))
            ->addOption(
                'user-id',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.debug.multisite.options.user-id'),
                1
            )
            ->setAliases(['dm']);
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $userID = $input->getOption('user-id');

        $currentUser = $this->site->setCurrentUser($userID);

        $sites = $this->site->getUserSites($currentUser->ID);

        $this->getIo()->info(
            sprintf(
                $this->trans('commands.debug.multisite.messages.user-sites'),
                $currentUser->display_name,
                $currentUser->ID
            )
        );

        $tableHeader = [
            $this->trans('commands.debug.multisite.messages.id'),
            $this->trans('commands.debug.multisite.messages.name'),
            $this->trans('commands.debug.multisite.messages.url'),
            $this->trans('commands.debug.multisite.messages.path'),
            $this->trans('commands.debug.multisite.messages.archived'),
            $this->trans('commands.debug.multisite.messages.mature'),
            $this->trans('commands.debug.multisite.messages.spam'),
            $this->trans('commands.debug.multisite.messages.deleted'),
        ];

        $tableRows = [];
        foreach ($sites as $site) {
            $tableRows[] = [
                $site->site_id,
                $site->blogname,
                $site->siteurl,
                $site->path,
                ($site->archived)?$this->trans('commands.common.status.checked'):$this->trans('commands.common.status.uncheked'),
                ($site->mature)?$this->trans('commands.common.status.checked'):$this->trans('commands.common.status.uncheked'),
                ($site->spam)?$this->trans('commands.common.status.checked'):$this->trans('commands.common.status.uncheked'),
                ($site->deleted)?$this->trans('commands.common.status.checked'):$this->trans('commands.common.status.uncheked')
            ];
        }

        $this->getIo()->table($tableHeader, $tableRows);

        return 0;
    }
}

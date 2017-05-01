<?php

/**
 * @file
 * Contains \WP\Console\Command\Multisite\DebugCommand.
 */

namespace WP\Console\Command\Multisite;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use WP\Console\Command\Shared\CommandTrait;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Utils\Site;

/**
 * Class SiteDebugCommand
 *
 * @package WP\Console\Command\Site
 */
class DebugCommand extends Command
{
    use CommandTrait;

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
            ->setName('multisite:debug')
            ->setDescription($this->trans('commands.multisite.debug.description'))
            ->setHelp($this->trans('commands.multisite.debug.help'))
            ->addOption(
                'user-id',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.multisite.debug.options.user-id'),
                1
            );
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);

        $userID = $input->getOption('user-id');

        $currentUser = $this->site->setCurrentUser($userID);

        $sites = $this->site->getUserSites($currentUser->ID);

        $io->info(
            sprintf(
                $this->trans('commands.multisite.debug.messages.user-sites'),
                $currentUser->display_name,
                $currentUser->ID
            )
        );

        $tableHeader = [
            $this->trans('commands.multisite.debug.messages.id'),
            $this->trans('commands.multisite.debug.messages.name'),
            $this->trans('commands.multisite.debug.messages.url'),
            $this->trans('commands.multisite.debug.messages.path'),
            $this->trans('commands.multisite.debug.messages.archived'),
            $this->trans('commands.multisite.debug.messages.mature'),
            $this->trans('commands.multisite.debug.messages.spam'),
            $this->trans('commands.multisite.debug.messages.deleted'),
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

        $io->table($tableHeader, $tableRows);

        return 0;
    }
}

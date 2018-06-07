<?php

/**
 * @file
 * Contains \WP\Console\Command\AboutCommand.
 */

namespace WP\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WP\Console\Core\Command\Command;
use WP\Console\Utils\Site;

class AboutCommand extends Command
{

    /**
     * @var Site
     */
    protected $site;

    /**
     * AboutCommand constructor.
     *
     * @param Site $site
     */
    public function __construct(
        Site $site
    ) {
        $this->site = $site;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('about')
            ->setDescription($this->trans('commands.about.description'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $application = $this->getApplication();

        $aboutTitle = sprintf(
            '%s (%s)',
            $application->getName(),
            $application->getVersion()
        );

        $this->getIo()->setDecorated(false);
        $this->getIo()->title($aboutTitle);
        $this->getIo()->setDecorated(true);

        $commands = [
            'init' => [
                $this->trans('commands.init.description'),
                'wp-console init --override --no-interaction'
            ],
            'links' => [
                $this->trans('commands.list.description'),
                'wp-console list',
            ]
        ];

        if (!$this->site->isInstalled()) {
            $commands['site-install'] = [
                $this->trans('commands.site.install.description'),
                sprintf(
                    'wp-console site:install'
                )];
        } elseif (!$this->site->isMultisite()) {
            $commands['site-install'] = [
                $this->trans('commands.site.multisite.install.description'),
                sprintf(
                    'wp-console site:multisite:install'
                )];
        }

        foreach ($commands as $command => $commandInfo) {
            $this->getIo()->writeln($commandInfo[0]);
            $this->getIo()->newLine();
            $this->getIo()->comment(sprintf('  %s', $commandInfo[1]));
            $this->getIo()->newLine();
        }

        $this->getIo()->setDecorated(false);
        $this->getIo()->section($this->trans('commands.self-update.description'));
        $this->getIo()->setDecorated(true);
        $this->getIo()->comment('  wp-console self-update');
        $this->getIo()->newLine();
    }
}

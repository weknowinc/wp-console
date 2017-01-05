<?php

/**
 * @file
 * Contains \WP\Console\Command\AboutCommand.
 */

namespace Wp\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use WP\Console\Command\Shared\CommandTrait;
use WP\Console\Core\Style\WPStyle;

class AboutCommand extends Command
{
    use CommandTrait;

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
        $io = new WPStyle($input, $output);
        $application = $this->getApplication();

        $aboutTitle = sprintf(
            '%s (%s)',
            $application->getName(),
            $application->getVersion()
        );

        $io->setDecorated(false);
        $io->title($aboutTitle);
        $io->setDecorated(true);

        $commands = [
            'init' => [
                $this->trans('commands.init.description'),
                'wp-console init --override --no-interaction'
            ],
            'site-install' => [
                $this->trans('commands.site.install.description'),
                sprintf(
                    'wp-console site:install'
                )
            ],
            'links' => [
                $this->trans('commands.list.description'),
                'wp-console list',
            ]
        ];

        foreach ($commands as $command => $commandInfo) {
            $io->writeln($commandInfo[0]);
            $io->newLine();
            $io->comment(sprintf('  %s', $commandInfo[1]));
            $io->newLine();
        }

        $io->setDecorated(false);
        $io->section($this->trans('commands.self-update.description'));
        $io->setDecorated(true);
        $io->comment('  wp-console self-update');
        $io->newLine();
    }
}

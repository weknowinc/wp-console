<?php

/**
 * @file
 * Contains \WP\Console\Command\Database\DropCommand.
 */

namespace WP\Console\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use WP\Console\Core\Command\Command;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Utils\Site;

/**
 * Class DropCommand
 *
 * @package WP\Console\Command\Database
 */
class DropCommand extends Command
{

    /**
     * @var Site
     */
    protected $site;

    /**
     * @var string
     */
    protected $appRoot;

    /**
     * DropCommand constructor.
     *
     * @param Site   $site
     * @param string $appRoot
     */
    public function __construct(Site $site, $appRoot)
    {
        $this->site = $site;
        $this->appRoot = $appRoot;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('database:drop')
            ->setDescription($this->trans('commands.database.drop.description'))
            ->setHelp($this->trans('commands.database.drop.help'))
            ->setAliases(['dbd']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);
        $yes = $input->getOption('yes');

        $this->site->loadLegacyFile('wp-config.php');
        global $wpdb;

        $command = sprintf(
            'mysql --no-defaults --no-auto-rehash -u%s -p%s -h%s -e $"drop database %s"',
            $wpdb->dbuser,
            $wpdb->dbpassword,
            $wpdb->dbhost,
            $wpdb->dbname
        );

        if (!$yes) {
            if (!$io->confirm(
                sprintf(
                    $this->trans('commands.database.drop.question.drop-tables'),
                    $wpdb->dbname
                ),
                true
            )
            ) {
                return 1;
            }
        }

        try {
            //Delete Database
            $process = new Process($command);
            $process->setWorkingDirectory('/usr/bin/env');
            $process->setTimeout(null);
            $process->run();
            $fs = new Filesystem();
            $configPath = sprintf('%s/wp-config.php', $this->appRoot);
            $fs->remove($configPath);
            $this->site->cacheFlush();

            $io->success(
                sprintf(
                    $this->trans('commands.database.drop.messages.database-drop'),
                    $wpdb->dbname
                )
            );
        } catch (\Exception $exception) {
            $io->error(
                sprintf(
                    $this->trans('commands.database.drop.errors.failed-database-drop'),
                    $wpdb->dbname
                )
            );
            return 1;
        }
        return 0;
    }
}

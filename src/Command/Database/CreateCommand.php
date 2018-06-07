<?php

/**
 * @file
 * Contains \WP\Console\Command\Database\CreateCommand.
 */

namespace WP\Console\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use WP\Console\Core\Command\Command;
use WP\Console\Utils\Site;

/**
 * Class CreateCommand
 *
 * @package WP\Console\Command\Database
 */
class CreateCommand extends Command
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
     * CreateCommand constructor.
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
            ->setName('database:create')
            ->setDescription($this->trans('commands.database.create.description'))
            ->setHelp($this->trans('commands.database.create.help'))
            ->addArgument(
                'dbname',
                InputArgument::REQUIRED,
                $this->trans('commands.database.create.arguments.dbname')
            )
            ->addArgument(
                'dbuser',
                InputArgument::OPTIONAL,
                $this->trans('commands.database.create.arguments.dbuser')
            )
            ->addArgument(
                'dbpassword',
                InputArgument::OPTIONAL,
                $this->trans('commands.database.create.arguments.dbpassword')
            )
            ->addArgument(
                'dbhost',
                InputArgument::OPTIONAL,
                $this->trans('commands.database.create.arguments.dbhost')
            )
            ->setAliases(['dbc']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->site->loadLegacyFile('wp-config.php');
        global $wpdb;

        $dbname = $input->getArgument('dbname');
        $dbuser = $wpdb ? $wpdb->dbuser : $input->getArgument('dbuser');
        $dbpassword = $wpdb ? $wpdb->dbpassword : $input->getArgument('dbpassword');
        $dbhost = $wpdb ? $wpdb->dbhost : $input->getArgument('dbhost');
        $yes = $input->getOption('yes');

        if (is_null($wpdb) && is_null($dbuser) && is_null($dbpassword)) {
            $this->getIo()->error($this->trans('commands.database.create.errors.empty-wpdb'));
            return 1;
        }

        if (!$yes) {
            if (!$this->getIo()->confirm(
                sprintf(
                    $this->trans('commands.database.create.question.create-tables'),
                    $dbname
                ),
                true
            )
            ) {
                return 1;
            }
        }

        if (is_null($wpdb)) {
            $command = sprintf(
                'mysql --no-defaults --no-auto-rehash -u%s -p%s -h%s -e "create database %s"',
                $dbuser,
                $dbpassword,
                $dbhost ? $dbhost : '127.0.0.1',
                $dbname
            );

            //Delete Database
            $process = new Process($command);
            $process->setWorkingDirectory('/usr/bin/env');
            $process->enableOutput();
            $process->setTimeout(null);
            $process->run();

            if (!$process->isSuccessful()) {
                $this->getIo()->error(
                    sprintf(
                        $this->trans('commands.database.create.errors.failed-database-create'),
                        $dbname
                    )
                );
                return 1;
            }
        } else {
            $result = $wpdb->query("CREATE DATABASE {$dbname}");

            if (!$result) {
                $this->getIo()->error(
                    sprintf(
                        $this->trans('commands.database.create.errors.failed-database-create'),
                        $dbname
                    )
                );
                return 1;
            }
        }

        $this->getIo()->success(
            sprintf(
                $this->trans('commands.database.create.messages.database-create'),
                $dbname
            )
        );
        return 0;
    }
}

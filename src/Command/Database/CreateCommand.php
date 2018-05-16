<?php

/**
 * @file
 * Contains \WP\Console\Command\Database\CreateCommand.
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
                InputArgument::REQUIRED,
                $this->trans('commands.database.create.arguments.dbuser')
            )
            ->addArgument(
                'dbpassword',
                InputArgument::REQUIRED,
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
        $io = new WPStyle($input, $output);
        $dbname = $input->getArgument('dbname');
        $dbuser = $input->getArgument('dbuser');
        $dbpassword = $input->getArgument('dbpassword');
        $dbhost = $input->getArgument('dbhost');
        $yes = $input->getOption('yes');

        $command = sprintf(
            'mysql --no-defaults --no-auto-rehash -u%s -p%s -h%s -e $"create database %s"',
            $dbuser,
            $dbpassword,
            $dbhost ? $dbhost : 'localhost',
            $dbname
        );

        if (!$yes) {
            if (!$io->confirm(
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

        try {
            //Delete Database
            $process = new Process($command);
            $process->setWorkingDirectory('/usr/bin/env');
            $process->setTimeout(null);
            $process->run();

            $io->success(
                sprintf(
                    $this->trans('commands.database.create.messages.database-create'),
                    $dbname
                )
            );
        } catch (\Exception $exception) {
            $io->error(
                sprintf(
                    $this->trans('commands.database.create.errors.failed-database-create'),
                    $dbname
                )
            );
            return 1;
        }
        return 0;
    }
}

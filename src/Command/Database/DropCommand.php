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
use WP\Console\Core\Command\Command;
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
            ->addArgument(
                'dbname',
                InputArgument::OPTIONAL,
                $this->trans('commands.database.create.arguments.dbname')
            )
            ->setAliases(['dbd']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dbname = $input->getArgument('dbname');
        $yes = $input->getOption('yes');

        $this->site->loadLegacyFile('wp-config.php');
        global $wpdb;

        if (!$yes) {
            if (!$this->getIo()->confirm(
                sprintf(
                    $this->trans('commands.database.drop.question.drop-tables'),
                    is_null($dbname) ? $wpdb->dbname : $dbname
                ),
                true
            )
            ) {
                return 1;
            }
        }

        $result = false;
        if (is_null($dbname)) {
            $table = $wpdb->get_results("show tables");
            foreach ($table as $value) {
                foreach ((array) $value as $table) {
                    $result = $wpdb->query("DROP TABLE {$table}");

                    if (!$result) {
                        $this->getIo()->error(
                            sprintf(
                                $this->trans('commands.database.drop.errors.failed-database-drop'),
                                $wpdb->dbname
                            )
                        );
                        return 1;
                    }
                }
            }

            $fs = new Filesystem();
            $configPath = sprintf('%s/wp-config.php', $this->appRoot);
            $fs->remove($configPath);
            $this->site->cacheFlush();
        } else {
            $result =  $wpdb->query("DROP DATABASE {$dbname}");
        }

        if (!$result) {
            $this->getIo()->error(
                sprintf(
                    $this->trans('commands.database.drop.errors.failed-database-drop'),
                    $wpdb->dbname
                )
            );
            return 1;
        }

        $this->getIo()->success(
            sprintf(
                $this->trans('commands.database.drop.messages.database-drop'),
                is_null($dbname) ? $wpdb->dbname : $dbname
            )
        );

        return 0;
    }
}

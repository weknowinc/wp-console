<?php

/**
 * @file
 * Contains \WP\Console\Command\Create\UsersCommand.
 */

namespace WP\Console\Command\Create;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WP\Console\Command\Shared\CreateTrait;
use WP\Console\Core\Command\Command;
use WP\Console\Utils\Create\UserData;
use WP\Console\Utils\Site;
use WP\Console\Utils\WordpressApi;

/**
 * Class UsersCommand
 *
 * @package WP\Console\Command\Create
 */
class UsersCommand extends Command
{
    use CreateTrait;

    /**
     * @var UserData
     */
    protected $createUserData;

    /**
     * @var Site
     */
    protected $site;

    /**
     * @var WordpressApi
     */
    protected $wordpressApi;

    /**
     * UsersCommand constructor.
     *
     * @param UserData     $createUserData
     * @param Site         $site
     * @param WordpressApi $wordpressApi
     */
    public function __construct(
        UserData $createUserData,
        Site $site,
        WordpressApi $wordpressApi
    ) {
        $this->createUserData = $createUserData;
        $this->site = $site;
        $this->wordpressApi = $wordpressApi;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('create:users')
            ->setDescription($this->trans('commands.create.users.description'))
            ->addArgument(
                'role',
                InputArgument::IS_ARRAY,
                $this->trans('commands.create.users.arguments.role')
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.create.users.options.limit')
            )
            ->addOption(
                'password',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.create.users.options.password')
            )
            ->addOption(
                'time-range',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.create.users.options.time-range')
            )
            ->setAliases(['cru']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $role = $input->getArgument('role');
        if (!$role) {
            $roles = $this->wordpressApi->getRoles();
            $role = $this->getIo()->choice(
                $this->trans('commands.create.users.questions.role'),
                array_keys($roles),
                null,
                false
            );


            $input->setArgument('role', $role);
        }

        $limit = $input->getOption('limit');
        if (!$limit) {
            $limit = $this->getIo()->ask(
                $this->trans('commands.create.users.questions.limit'),
                10
            );
            $input->setOption('limit', $limit);
        }

        $password = $input->getOption('password');
        if (!$password) {
            $password = $this->getIo()->askEmpty(
                $this->trans('commands.create.users.questions.password'),
                null
            );

            $input->setOption('password', $password);
        }

        $timeRange = $input->getOption('time-range');
        if (!$timeRange) {
            $timeRanges = $this->getTimeRange();

            $timeRange = $this->getIo()->choice(
                $this->trans('commands.create.nodes.questions.time-range'),
                array_values($timeRanges)
            );

            $input->setOption('time-range', array_search($timeRange, $timeRanges));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $role = $input->getArgument('role');
        $limit = $input->getOption('limit')?:25;
        $password = $input->getOption('password');
        $timeRange = $input->getOption('time-range')?:31536000;

        if (!$role) {
            $role = $this->site->getOption('default_role');
        }

        $users = $this->createUserData->create(
            $role,
            $limit,
            $password,
            $timeRange
        );

        $tableHeader = [
            $this->trans('commands.create.users.messages.user-id'),
            $this->trans('commands.create.users.messages.username'),
            $this->trans('commands.create.users.messages.password'),
            $this->trans('commands.create.users.messages.role'),
            $this->trans('commands.create.users.messages.registered'),
        ];

        if ($users['success']) {
            $this->getIo()->table($tableHeader, $users['success']);

            $this->getIo()->success(
                sprintf(
                    $this->trans('commands.create.users.messages.created-users'),
                    $limit
                )
            );
        }

        return 0;
    }
}

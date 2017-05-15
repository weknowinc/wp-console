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
use Symfony\Component\Console\Command\Command;
use WP\Console\Command\Shared\CommandTrait;
use WP\Console\Command\Shared\CreateTrait;
use WP\Console\Utils\Create\UserData;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Utils\Site;

/**
 * Class UsersCommand
 *
 * @package WP\Console\Command\Create
 */
class UsersCommand extends Command
{
    use CommandTrait;
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
     * UsersCommand constructor.
     *
     * @param UserData  $createUserData
     * @param Site       $site
     */
    public function __construct(
        UserData $createUserData,
        Site $site
    ) {
        $this->createUserData = $createUserData;
        $this->site = $site;
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
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);

        $role = $input->getArgument('role');
        if (!$role) {
            $roles = $this->site->getRoles();
            $role = $io->choice(
                $this->trans('commands.create.users.questions.role'),
                array_keys($roles),
                null,
                false
            );


            $input->setArgument('role', $role);
        }

        $limit = $input->getOption('limit');
        if (!$limit) {
            $limit = $io->ask(
                $this->trans('commands.create.users.questions.limit'),
                10
            );
            $input->setOption('limit', $limit);
        }

        $password = $input->getOption('password');
        if (!$password) {
            $password = $io->askEmpty(
                $this->trans('commands.create.users.questions.password'),
                null
            );

            $input->setOption('password', $password);
        }

        $timeRange = $input->getOption('time-range');
        if (!$timeRange) {
            $timeRanges = $this->getTimeRange();

            $timeRange = $io->choice(
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
        $io = new WPStyle($input, $output);

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
            $io->table($tableHeader, $users['success']);

            $io->success(
                sprintf(
                    $this->trans('commands.create.users.messages.created-users'),
                    $limit
                )
            );
        }

        return 0;
    }
}

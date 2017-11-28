<?php

/**
 * @file
 * Contains \WP\Console\Command\Roles\DebugCommand.
 */

namespace WP\Console\Command\Debug;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WP\Console\Core\Command\Command;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Utils\WordpressApi;

/**
 * Class RolesCommand
 *
 * @package WP\Console\Command\Debug
 */
class RolesCommand extends Command
{
    /**
     * @var WordpressApi
     */
    protected $wordpressApi;

    /**
     * DebugCommand constructor.
     *
     * @param WordpressApi $wordpressApi
     */
    public function __construct(
        WordpressApi $wordpressApi
    ) {
        $this->wordpressApi = $wordpressApi;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('debug:roles')
            ->setDescription($this->trans('commands.debug.roles.description'))
            ->setAliases(['dusr']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);

        $roles = $this->wordpressApi->getRoles();

        $tableHeader = [
            $this->trans('commands.debug.roles.messages.role-id'),
            $this->trans('commands.debug.roles.messages.role-name'),
        ];

        $tableRows = [];
        foreach ($roles as $roleId => $role) {
            $tableRows[] = [
                $roleId,
                $role
            ];
        }

        $io->table($tableHeader, $tableRows);
    }
}

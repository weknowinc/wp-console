<?php
/**
 * @file
 * Contains \WP\Console\Command\Role\NewCommand.
 */

namespace WP\Console\Command\Role;

use Symfony\Component\Console\Input\InputOption;
use WP\Console\Core\Utils\StringConverter;
use WP\Console\Utils\Site;
use WP\Console\Utils\Validator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use WP\Console\Core\Command\Command;
use WP\Console\Utils\WordpressApi;
use WP\Console\Command\Shared\ConfirmationTrait;

class NewCommand extends Command
{
    use ConfirmationTrait;

    /**
     * @var WordpressApi
     */
    protected $wordpressApi;

    /**
     * @var Site
     */
    protected $site;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * NewCommand constructor.
     *
     * @param WordpressApi    $wordpressApi
     * @param Site            $site
     * @param Validator       $validator
     * @param StringConverter $stringConverter
     */
    public function __construct(
        WordpressApi $wordpressApi,
        Site $site,
        Validator $validator,
        StringConverter $stringConverter
    ) {
        $this->wordpressApi = $wordpressApi;
        $this->site = $site;
        $this->validator = $validator;
        $this->stringConverter = $stringConverter;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('role:new')
            ->setDescription($this->trans('commands.role.new.description'))
            ->setHelp($this->trans('commands.role.new.help'))
            ->addArgument(
                'rolename',
                InputArgument::OPTIONAL,
                $this->trans('commands.role.new.argument.rolename')
            )
            ->addArgument(
                'machine-name',
                InputArgument::OPTIONAL,
                $this->trans('commands.role.new.argument.machine-name')
            )
            ->addOption(
                'capabilities',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.role.new.option.capabilities')
            )->setAliases(['rn']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $rolename = $input->getArgument('rolename');
        $machine_name= $input->getArgument('machine-name');
        $capabilities= $input->getOption('capabilities');

        $role = $this->createRole(
            $rolename,
            $machine_name,
            $capabilities
        );

        $tableHeader = [
            $this->trans('commands.role.new.messages.role-id'),
            $this->trans('commands.role.new.messages.role-name'),
        ];

        if ($role['success']) {
            $this->getIo()->success(
                sprintf(
                    $this->trans('commands.role.new.messages.role-created'),
                    $role['success'][0]['role-name']
                )
            );

            $this->getIo()->table($tableHeader, $role['success']);

            return 0;
        }

        if ($role['error']) {
            $this->getIo()->error($role['error']['error']);

            return 1;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('rolename');
        if (!$name) {
            $name = $this->getIo()->ask($this->trans('commands.role.new.questions.rolename'));
            $input->setArgument('rolename', $name);
        }

        $machine_name = $input->getArgument('machine-name');
        if (!$machine_name) {
            $machine_name = $this->getIo()->ask(
                $this->trans('commands.role.new.questions.machine-name'),
                $this->stringConverter->createMachineName($name),
                function ($machine_name) {
                    $roles = $this->wordpressApi->getRoles();
                    if (array_key_exists($machine_name, $roles)) {
                        throw new \Exception('The machine name is already exist');
                    }

                    return $this->validator->validateMachineName($machine_name);
                }
            );
            $input->setArgument('machine-name', $machine_name);
        }

        $capabilities = $input->getOption('capabilities');
        if (!$capabilities) {
            $roles = array_keys($this->wordpressApi->getRoles());

            $menu_options = [
                $this->trans('commands.role.new.messages.capabilities-menu.import'),
                $this->trans('commands.role.new.messages.capabilities-menu.showAll'),
                ];

            $menu = $this->getIo()->choice(
                $this->trans('commands.role.new.questions.capabilities'),
                $menu_options
            );

            $capabilities_collection = [];

            if (array_search($menu, $menu_options) == 0) {
                $choice_role = $this->getIo()->choice(
                    $this->trans('commands.role.new.questions.capabilities'),
                    $roles
                );

                $capabilities_collection = $this->wordpressApi->getCapabilities($choice_role);
            } else {
                $this->getIo()->writeln($this->trans('commands.common.questions.capabilities.message'));
                $capabilities = $this->wordpressApi->getCapabilities();
                while (true) {
                    $capability = $this->getIo()->choiceNoList(
                        $this->trans('commands.common.questions.capabilities.name'),
                        $capabilities,
                        null,
                        true
                    );

                    $capability = trim($capability);
                    if (empty($capability)) {
                        break;
                    }

                    $capabilities_collection[$capability] = true;
                    $capability_key = array_search($capability, $capabilities, true);

                    if ($capability_key >= 0) {
                        unset($capabilities[$capability_key]);
                    }
                }
            }

            $input->setOption('capabilities', [$capabilities_collection]);
        }
    }

    /**
     * Create a role to the application
     *
     * @param $rolename
     * @param $machine_name
     * @param $capabilities
     *
     * @return $array
     */
    private function createRole($rolename, $machine_name, $capabilities)
    {
        $this->site->loadLegacyFile('wp-includes/capabilities.php');

        $result = [];

        try {
            $role = add_role(
                $machine_name,
                $rolename,
                $capabilities[0]
            );

            $result['success'][] = [
                'role-id' => $machine_name,
                'role-name' => $role->name
            ];
        } catch (\Exception $e) {
            $result['error'] = [
                'vid' => $machine_name,
                'name' => $rolename,
                'error' => 'Error: ' . get_class($e) . ', code: ' . $e->getCode() . ', message: ' . $e->getMessage()
            ];
        }
        return $result;
    }
}

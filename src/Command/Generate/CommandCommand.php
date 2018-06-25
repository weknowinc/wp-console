<?php

/**
 * @file
 * Contains \WP\Console\Command\Generate\CommandCommand.
 */

namespace WP\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use WP\Console\Command\Shared\PluginTrait;
use WP\Console\Command\Shared\ServicesTrait;
use WP\Console\Command\Shared\ConfirmationTrait;
use WP\Console\Core\Command\ContainerAwareCommand;
use WP\Console\Generator\CommandGenerator;
use WP\Console\Core\Utils\StringConverter;
use WP\Console\Extension\Manager;
use WP\Console\Utils\Validator;

class CommandCommand extends ContainerAwareCommand
{
    use ConfirmationTrait;
    use PluginTrait;
    use ServicesTrait;

    /**
     * @var CommandGenerator
     */
    protected $generator;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * CommandCommand constructor.
     *
     * @param CommandGenerator $generator
     * @param Manager          $extensionManager
     * @param Validator        $validator
     * @param StringConverter  $stringConverter
     */
    public function __construct(
        CommandGenerator $generator,
        Manager $extensionManager,
        Validator $validator,
        StringConverter $stringConverter
    ) {
        $this->generator = $generator;
        $this->extensionManager = $extensionManager;
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
            ->setName('generate:command')
            ->setDescription($this->trans('commands.generate.command.description'))
            ->setHelp($this->trans('commands.generate.command.help'))
            ->addOption(
                'plugin',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.plugin')
            )
            ->addOption(
                'class',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.command.options.class')
            )
            ->addOption(
                'name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.command.options.name')
            )
            ->addOption(
                'services',
                '',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                $this->trans('commands.common.options.services')
            )
            ->setAliases(['gc']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $plugin = $input->getOption('plugin');
        $pluginNameSpace = $this->stringConverter->humanToCamelCase($plugin);
        $pluginCamelCaseMachineName = $this->stringConverter->createMachineName($this->stringConverter->humanToCamelCase($plugin));

        $class = $this->validator->validateCommandName($input->getOption('class'));
        $name = $input->getOption('name');
        $services = $input->getOption('services');

        // @see use WP\Console\Command\Shared\ConfirmationTrait::confirmOperation
        if (!$this->confirmOperation()) {
            return 1;
        }

        // @see use WP\Console\Command\Shared\ServicesTrait::buildServices
        $build_services = $this->buildServices($services);

        $this->generator->generate(
            [
                'plugin' => $plugin,
                'class_name' => $class,
                'pluginNameSpace' => $pluginNameSpace,
                'pluginCamelCaseMachineName' => $pluginCamelCaseMachineName,
                'name' => $name,
                'services' => $build_services,
            ]
        );

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // --plugin
        $plugin = $input->getOption('plugin');
        if (!$plugin) {
            $plugin = $this->pluginQuestion();
            $input->setOption('plugin', $plugin);
        }

        $class = $input->getOption('class');
        if (!$class) {
            $class = $this->getIo()->ask(
                $this->trans('commands.generate.command.questions.class'),
                'DefaultCommand',
                function ($class) {
                    return $this->validator->validateCommandName($class);
                }
            );
            $input->setOption('class', $class);
        }

        //Get plugin for options
        $plugin = $input->getOption('plugin');

        $name = $input->getOption('name');
        if (!$name) {
            $name = $this->getIo()->ask(
                $this->trans('commands.generate.command.questions.name'),
                sprintf(
                    '%s:default',
                    $this->stringConverter->createMachineName(
                        $this->stringConverter->humanToCamelCase($plugin)
                    )
                )
            );
            $input->setOption('name', $name);
        }

        $services = $input->getOption('services');
        if (empty($services)) {
            $services = $this->servicesQuestion();
            $input->setOption('services', $services);
        }
    }
}

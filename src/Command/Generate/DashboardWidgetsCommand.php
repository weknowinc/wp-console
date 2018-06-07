<?php

/**
 * @file
 * Contains \WP\Console\Command\Generate\DashboardWidgetsCommand.
 */

namespace WP\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WP\Console\Command\Shared\ConfirmationTrait;
use WP\Console\Command\Shared\PluginTrait;
use WP\Console\Core\Command\Command;
use WP\Console\Extension\Manager;
use WP\Console\Generator\DashboardWidgetsGenerator;
use WP\Console\Utils\Validator;
use WP\Console\Core\Utils\StringConverter;

class DashboardWidgetsCommand extends Command
{
    use PluginTrait;
    use ConfirmationTrait;

    /**
     * @var DashboardWidgetsGenerator
     */
    protected $generator;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * DashboardWidgetsCommand constructor.
     *
     * @param DashboardWidgetsGenerator $generator
     * @param Manager                   $extensionManager
     * @param Validator                 $validator
     * @param StringConverter           $stringConverter
     */
    public function __construct(
        DashboardWidgetsGenerator $generator,
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
            ->setName('generate:dashboard:widgets')
            ->setDescription($this->trans('commands.generate.dashboard.widgets.description'))
            ->setHelp($this->trans('commands.generate.dashboard.widgets.help'))
            ->addOption(
                'plugin',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.plugin')
            )
            ->addOption(
                'class-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.dashboard.widgets.options.class-name')
            )
            ->addOption(
                'id',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.dashboard.widgets.options.id')
            )
            ->addOption(
                'title',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.dashboard.widgets.options.title')
            )
            ->addOption(
                'render-function',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.dashboard.widgets.options.render-function')
            )
            ->addOption(
                'submission-function',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.dashboard.widgets.options.submission-function')
            )
            ->addOption(
                'callback-arguments',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.dashboard.widgets.options.callback-arguments')
            )
            ->addOption(
                'text-domain',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.dashboard.widgets.options.text-domain')
            )
            ->setAliases(['gdw']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // @see use WP\Console\Command\Shared\ConfirmationTrait::confirmOperation
        if (!$this->confirmOperation()) {
            return;
        }

        $plugin = $plugin = $this->validator->validatePluginName($input->getOption('plugin'));
        $class_name = $this->validator->validateClassName($input->getOption('class-name'));
        $id = $this->validator->validateMachineName($input->getOption('id'));
        $title = $input->getOption('title');
        $render_function = $this->validator->validateFunctionName($input->getOption('render-function'));
        $submission_function = $input->getOption('submission-function');
        $callback_arguments = $input->getOption('callback-arguments');
        $text_domain = $input->getOption('text-domain');

        $this->generator->generate(
            $plugin,
            $class_name,
            $id,
            $title,
            $render_function,
            $submission_function,
            $callback_arguments,
            $text_domain
        );
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

        // --class name
        $class_name = $input->getOption('class-name');
        if (!$class_name) {
            $class_name = $this->getIo()->ask(
                $this->trans('commands.generate.dashboard.widgets.questions.class-name'),
                'CustomDashboardWidgets',
                function ($value) {
                    if (!strlen(trim($value))) {
                        throw new \Exception('The Class name can not be empty');
                    }
                    return $this->stringConverter->humanToCamelCase($value);
                }
            );
            $input->setOption('class-name', $class_name);
        }

        // --id
        $id = $input->getOption('id');
        if (!$id) {
            $id = $this->getIo()->ask(
                $this->trans('commands.generate.dashboard.widgets.questions.id'),
                strtolower($class_name),
                function ($value) {
                    return $this->stringConverter->createMachineName($value);
                }
            );
        }
        $input->setOption('id', $id);

        // --title
        $title = $input->getOption('title');
        if (!$title) {
            $title = $this->getIo()->ask(
                $this->trans('commands.generate.dashboard.widgets.questions.title'),
                'Example Dashboard Widget'
            );
        }
        $input->setOption('title', $title);

        // --render function
        $render_function = $input->getOption('render-function');
        if (!$render_function) {
            $render_function = $this->getIo()->ask(
                $this->trans('commands.generate.dashboard.widgets.questions.render-function'),
                $id.'_callback',
                function ($value) {
                    return $this->validator->validateFunctionName($value);
                }
            );
        }
        $input->setOption('render-function', $render_function);

        // --submission function
        $submission_function = $input->getOption('submission-function');
        if (!$submission_function) {
            $submission_function = $this->getIo()->confirm(
                $this->trans('commands.generate.dashboard.widgets.questions.submission-function'),
                false
            );
        }
        $input->setOption('submission-function', $submission_function);

        // --callback arguments
        $callback_arguments = $input->getOption('callback-arguments');
        if (!$callback_arguments) {
            $callback_arguments = $this->getIo()->askEmpty(
                $this->trans('commands.generate.dashboard.widgets.questions.callback-arguments'),
                function ($value) {
                    if (!empty($value)) {
                        $validate = array($value);
                        if (!is_array($validate)) {
                            throw new \Exception('Do not have the correct format');
                        }
                    }
                    return $value;
                }
            );
        }
        $input->setOption('callback-arguments', $callback_arguments);

        // --text domain
        $text_domain = $input->getOption('text-domain');
        if (!$text_domain) {
            $text_domain = $this->getIo()->askEmpty(
                $this->trans('commands.generate.dashboard.widgets.questions.text-domain')
            );
        }
        $input->setOption('text-domain', $text_domain);
    }
}

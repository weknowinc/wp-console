<?php

/**
 * @file
 * Contains \WP\Console\Command\Generate\WidgetCommand.
 */

namespace WP\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use WP\Console\Command\Shared\FieldsTypeTrait;
use WP\Console\Command\Shared\PluginTrait;
use WP\Console\Command\Shared\ConfirmationTrait;
use WP\Console\Core\Command\Command;
use WP\Console\Generator\WidgetGenerator;
use WP\Console\Core\Utils\StringConverter;
use WP\Console\Extension\Manager;
use WP\Console\Utils\Validator;

class WidgetCommand extends Command
{
    use ConfirmationTrait;
    use PluginTrait;
    use FieldsTypeTrait;

    /**
     * @var WidgetGenerator
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
     * WidgetCommand constructor.
     *
     * @param WidgetGenerator $generator
     * @param Manager         $extensionManager
     * @param Validator       $validator
     * @param StringConverter $stringConverter
     */
    public function __construct(
        WidgetGenerator $generator,
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
            ->setName('generate:widget')
            ->setDescription($this->trans('commands.generate.widget.description'))
            ->setHelp($this->trans('commands.generate.widget.help'))
            ->addOption(
                'plugin',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.plugin')
            )
            ->addOption(
                'class-name',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.widget.options.class-name')
            )
            ->addOption(
                'widget-id',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.widget.options.widget-id')
            )
            ->addOption(
                'title',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.widget.options.title')
            )
            ->addOption(
                'description',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.widget.options.description')
            )
            ->addOption(
                'widget-class-name',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.widget.options.widget-class-name')
            )
            ->addOption(
                'widget-items',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.widget.options.widget-items')
            )
            ->setAliases(['gwd']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $plugin = $input->getOption('plugin');
        $class_name = $this->validator->validateClassName($input->getOption('class-name'));
        $widget_id = $input->getOption('widget-id');
        $title = $input->getOption('title');
        $description = $input->getOption('description');
        $widget_class_name = $input->getOption('widget-class-name');
        $widget_items = $input->getOption('widget-items');

        // @see use WP\Console\Command\Shared\ConfirmationTrait::confirmOperation
        if (!$this->confirmOperation()) {
            return 1;
        }

        $this->generator->generate(
            [
            "plugin" => $plugin,
            "class_name" => $class_name,
            "widget_id" => $widget_id,
            "title" => $title,
            "description" => $description,
            "widget_class_name" => $widget_class_name,
            "widget_items" => $widget_items,
            ]
        );

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $stringUtils = $this->stringConverter;

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
                $this->trans('commands.generate.widget.questions.class-name'),
                'DefaultWidget',
                function ($value) use ($stringUtils) {
                    if (!strlen(trim($value))) {
                        throw new \Exception('The Class name can not be empty');
                    }
                    return $stringUtils->humanToCamelCase($value);
                }
            );
            $input->setOption('class-name', $class_name);
        }

        // --widget id
        $widget_id = $input->getOption('widget-id');
        if (!$widget_id) {
            $widget_id = $this->getIo()->ask(
                $this->trans('commands.generate.widget.questions.widget-id'),
                $stringUtils->camelCaseToUnderscore($class_name)
            );
        }
        $input->setOption('widget-id', $widget_id);

        // -- title
        $title = $input->getOption('title');
        if (!$title) {
            $title = $this->getIo()->ask(
                $this->trans('commands.generate.widget.questions.title'),
                'First widget'
            );
        }
        $input->setOption('title', $title);

        // --description
        $description = $input->getOption('description');
        if (!$description) {
            $description = $this->getIo()->ask(
                $this->trans('commands.generate.widget.questions.description'),
                'My '.strtolower($title)
            );
            $input->setOption('description', $description);
        }

        // --widget class name
        $widget_class_name = $input->getOption('widget-class-name');
        if (!$widget_class_name) {
            $widget_class_name = $this->getIo()->ask(
                $this->trans('commands.generate.widget.questions.widget-class-name')
            );
        }
        $input->setOption('widget-class-name', $widget_class_name);

        // --widget items
        $widget_items = $input->getOption('widget-items');
        if (!$widget_items) {
            if ($this->getIo()->confirm(
                $this->trans('commands.generate.widget.questions.widget-items.widget-add'),
                true
            )
            ) {
                // @see \WP\Console\Command\Shared\FieldsTypeTrait::fieldsQuestion
                $widget_items = $this->fieldsQuestion('widget', 'widget-items');
                $input->setOption('widget-items', $widget_items);
            }
        }
    }
}

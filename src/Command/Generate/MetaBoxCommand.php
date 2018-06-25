<?php

/**
 * @file
 * Contains \WP\Console\Command\Generate\MetaBoxCommand.
 */

namespace WP\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WP\Console\Command\Shared\ConfirmationTrait;
use WP\Console\Command\Shared\FieldsTypeTrait;
use WP\Console\Command\Shared\PluginTrait;
use WP\Console\Core\Command\Command;
use WP\Console\Extension\Manager;
use WP\Console\Generator\MetaBoxGenerator;
use WP\Console\Utils\Validator;
use WP\Console\Core\Utils\StringConverter;

class MetaBoxCommand extends Command
{
    use PluginTrait;
    use ConfirmationTrait;
    use FieldsTypeTrait;
    
    /**
     * @var MetaBoxGenerator
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
     * @var string
     */
    protected $twigtemplate;
    
    
    /**
     * MetaBoxCommand constructor.
     *
     * @param MetaBoxGenerator $generator
     * @param Manager          $extensionManager
     * @param Validator        $validator
     * @param StringConverter  $stringConverter
     */
    public function __construct(
        MetaBoxGenerator $generator,
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
            ->setName('generate:metabox')
            ->setDescription($this->trans('commands.generate.metabox.description'))
            ->setHelp($this->trans('commands.generate.metabox.help'))
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
                $this->trans('commands.generate.metabox.options.class-name')
            )
            ->addOption(
                'metabox-id',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.metabox.options.metabox-id')
            )
            ->addOption(
                'title',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.metabox.options.title')
            )
            ->addOption(
                'callback-function',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.metabox.options.callback-function')
            )
            ->addOption(
                'screen',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.metabox.options.screen')
            )
            ->addOption(
                'page-location',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.metabox.options.page-location')
            )
            ->addOption(
                'priority',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.metabox.options.priority')
            )
            ->addOption(
                'metabox-items',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.metabox.options.metabox-items')
            )
            ->addOption(
                'wp-nonce',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.metabox.options.page-location')
            )
            ->addOption(
                'auto-save',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.metabox.options.priority')
            )
            ->setAliases(['gmb']);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // @see use WP\Console\Command\Shared\ConfirmationTrait::confirmOperation
        if (!$this->confirmOperation()) {
            return 1;
        }
        
        $plugin = $plugin = $this->validator->validatePluginName($input->getOption('plugin'));
        $class_name = $this->validator->validateClassName($input->getOption('class-name'));
        $metabox_id = $input->getOption('metabox-id');
        $title = $input->getOption('title');
        $callback_function = $this->validator->validateFunctionName($input->getOption('callback-function'));
        $screen = $input->getOption('screen');
        $page_location = $input->getOption('page-location');
        $priority = $input->getOption('priority');
        $metabox_fields = $input->getOption('metabox-items');
        $wp_nonce = $input->getOption('wp-nonce');
        $auto_save = $input->getOption('auto-save');

        $this->generator->generate(
            [
            "plugin" => $plugin,
            "class_name" => $class_name,
            "metabox_id" => $metabox_id,
            "title" => $title,
            "callback_function" => $callback_function,
            "screen" => $screen,
            "page_location" => $page_location,
            "priority" => $priority,
            "metabox_items" => $metabox_fields,
            "wp_nonce" => $wp_nonce,
            "auto_save" => $auto_save,

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
                $this->trans('commands.generate.metabox.questions.class-name'),
                'DefaultMetabox',
                function ($value) use ($stringUtils) {
                    if (!strlen(trim($value))) {
                        throw new \Exception('The Class name can not be empty');
                    }
                    return $stringUtils->humanToCamelCase($value);
                }
            );
            $input->setOption('class-name', $class_name);
        }
        
        // --metabox id
        $metabox_id = $input->getOption('metabox-id');
        if (!$metabox_id) {
            $metabox_id = $this->getIo()->ask(
                $this->trans('commands.generate.metabox.questions.metabox-id'),
                $stringUtils->camelCaseToUnderscore($class_name)
            );
        }
        $input->setOption('metabox-id', $metabox_id);
        
        // --metabox title
        $title = $input->getOption('title');
        if (!$title) {
            $title = $this->getIo()->ask(
                $this->trans('commands.generate.metabox.questions.title'),
                ucwords($stringUtils->camelCaseToHuman($class_name))
            );
        }
        $input->setOption('title', $title);
        
        // --callback_function
        $callback_function = $input->getOption('callback-function');
        if (!$callback_function) {
            $callback_function = $this->getIo()->ask(
                $this->trans('commands.generate.metabox.questions.callback-function'),
                $stringUtils->camelCaseToUnderscore($class_name) . '_callback',
                function ($function_name) {
                    return $this->validator->validateFunctionName($function_name);
                }
            );
            $input->setOption('callback-function', $callback_function);
        }
        
        // --screen
        $screen_options = ['post', 'page', 'custom'];
        $screen = $input->getOption('screen');
        if (!$screen) {
            $screen = $this->getIo()->choiceNoList(
                $this->trans('commands.generate.metabox.questions.screen'),
                $screen_options
            );
        }
        $input->setOption('screen', $screen);
        
        // --page location
        $options_page_location = ['advanced', 'normal', 'side'];
        $page_location = $input->getOption('page-location');
        if (!$page_location) {
            $page_location = $this->getIo()->choiceNoList(
                $this->trans('commands.generate.metabox.questions.page-location'),
                $options_page_location
            );
            $input->setOption('page-location', $page_location);
        }
        
        // --priority
        $options_priority = ['default', 'core', 'high', 'low'];
        $priority = $input->getOption('priority');
        if (!$priority) {
            $priority = $this->getIo()->choiceNoList(
                $this->trans('commands.generate.metabox.questions.priority'),
                $options_priority
            );
        }
        $input->setOption('priority', $priority);
        
        
        // -- metabox items
        $metabox_items = $input->getOption('metabox-items');
        if (!$metabox_items) {
            if ($this->getIo()->confirm(
                $this->trans('commands.generate.metabox.questions.metabox-items.metabox-add'),
                true
            )
            ) {
                // @see \WP\Console\Command\Shared\FieldsTypeTrait::fieldsQuestion
                $metabox_items = $this->fieldsQuestion('metabox', 'metabox-items');
                $input->setOption('metabox-items', $metabox_items);
            }
        }
        
        if (!empty($metabox_items)) {
            // --wp nonce
            $wp_nonce = $input->getOption('wp-nonce');
            if (!$wp_nonce) {
                $wp_nonce = $this->getIo()->confirm(
                    $this->trans('commands.generate.metabox.questions.wp-nonce'),
                    true
                );
                $input->setOption('wp-nonce', $wp_nonce);
            }
            
            // --auto save
            $auto_save = $input->getOption('auto-save');
            if (!$auto_save) {
                if ($auto_save = $this->getIo()->confirm(
                    $this->trans('commands.generate.metabox.questions.auto-save'),
                    true
                )
                ) {
                    $input->setOption('auto-save', $auto_save);
                }
            }
        }
    }
}

<?php

/**
 * @file
 * Contains \WP\Console\Command\Generate\MetaBoxCommand.
 */

namespace WP\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use WP\Console\Command\Shared\ConfirmationTrait;
use WP\Console\Command\Shared\MetaboxTrait;
use WP\Console\Command\Shared\PluginTrait;
use WP\Console\Extension\Manager;
use WP\Console\Generator\MetaBoxGenerator;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Utils\Validator;
use WP\Console\Command\Shared\CommandTrait;
use WP\Console\Core\Utils\StringConverter;

class MetaBoxCommand extends Command
{
    use PluginTrait;
    use ConfirmationTrait;
    use CommandTrait;
    
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
                'fields-metabox',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.metabox.options.fields-metabox')
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
        $io = new WPStyle($input, $output);
        
        // @see use WP\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io)) {
            return;
        }
        
        $plugin = $plugin = $this->validator->validatePluginName($input->getOption('plugin'));
        $class_name = $input->getOption('class-name');
        $metabox_id = $input->getOption('metabox-id');
        $title = $input->getOption('title');
        $callback_function = $input->getOption('callback-function');
        $screen = $input->getOption('screen');
        $page_location = $input->getOption('page-location');
        $priority = $input->getOption('priority');
        $fields_metabox = $input->getOption('fields-metabox');
        $wp_nonce = $input->getOption('wp-nonce');
        $auto_save = $input->getOption('auto-save');

        $this->generator->generate(
            $plugin,
            $class_name,
            $metabox_id,
            $title,
            $callback_function,
            $screen,
            $page_location,
            $priority,
            $fields_metabox,
            $wp_nonce,
            $auto_save
        );
    }
    
    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);
        $stringUtils = $this->stringConverter;
        
        // --plugin
        $plugin = $input->getOption('plugin');
        if (!$plugin) {
            $plugin = $this->pluginQuestion($io);
            $input->setOption('plugin', $plugin);
        }
        
        //Get plugin for options
        $plugin = $input->getOption('plugin');
        // --class name
        $class_name = $input->getOption('class-name');
        if (!$class_name) {
            $class_name = $io->ask(
                $this->trans('commands.generate.metabox.questions.class-name'),
                $stringUtils->humanToCamelCase($plugin).'MetaBox',
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
            $metabox_id = $io->ask(
                $this->trans('commands.generate.metabox.questions.metabox-id'),
                str_replace(' ', '_', $plugin) .'_meta_box'
            );
        }
        $input->setOption('metabox-id', $metabox_id);
        
        // --metabox title
        $title = $input->getOption('title');
        if (!$title) {
            $title = $io->ask(
                $this->trans('commands.generate.metabox.questions.title'),
                ucwords($stringUtils->camelCaseToHuman($plugin)).' Meta Box'
            );
        }
        $input->setOption('title', $title);
        
        // --callback_function
        $callback_function = $input->getOption('callback-function');
        if (!$callback_function) {
            $callback_function = $io->ask(
                $this->trans('commands.generate.metabox.questions.callback-function'),
                str_replace(' ', '_', $plugin) .'_meta_box_callback'
            );
            $input->setOption('callback-function', $callback_function);
        }
        
        // --screen
        $screen_options = ['post', 'page', 'custom'];
        $screen = $input->getOption('screen');
        if (!$screen) {
            $screen = $io->choiceNoList(
                $this->trans('commands.generate.metabox.questions.screen'),
                $screen_options
            );
        }
        $input->setOption('screen', $screen);
        
        // --page location
        $options_page_location = ['advanced', 'normal', 'side'];
        $page_location = $input->getOption('page-location');
        if (!$page_location) {
            $page_location = $io->choiceNoList(
                $this->trans('commands.generate.metabox.questions.page-location'),
                $options_page_location
            );
            $input->setOption('page-location', $page_location);
        }
        
        // --priority
        $options_priority = ['default', 'core', 'high', 'low'];
        $priority = $input->getOption('priority');
        if (!$priority) {
            $priority = $io->choiceNoList(
                $this->trans('commands.generate.metabox.questions.priority'),
                $options_priority
            );
        }
        $input->setOption('priority', $priority);
        
        
        // --field metabox
        $fields_metabox = $input->getOption('fields-metabox');
        if (!$fields_metabox) {
            if ($io->confirm(
                $this->trans('commands.generate.metabox.questions.fields-metabox'),
                true
            )
            ) {
                // @see \WP\Console\Command\Shared\MetaboxTrait::fieldsMetaboxQuestion
                $fields_metabox = $this->fieldMetaboxQuestion($io);
                $input->setOption('fields-metabox', $fields_metabox);
            }
        }
        
        if (!empty($fields_metabox)) {
            // --wp nonce
            $wp_nonce = $input->getOption('wp-nonce');
            if (!$wp_nonce) {
                if ($io->confirm(
                    $this->trans('commands.generate.metabox.questions.wp-nonce'),
                    true
                )
                ) {
                    $input->setOption('wp-nonce', $wp_nonce);
                }
            }
            
            // --auto save
            $auto_save = $input->getOption('auto-save');
            if (!$auto_save) {
                if ($io->confirm(
                    $this->trans('commands.generate.metabox.questions.auto-save'),
                    true
                )
                ) {
                    $input->setOption('auto-save', $auto_save);
                }
            }
        }
    }

    public function fieldMetaboxQuestion(WPStyle $io)
    {
        $stringConverter = $this->stringConverter;

        $fields = [];
        $fields_options = ['select' ,'checkbox', 'color', 'date', 'email', 'file', 'image', 'month', 'number',
            'radio','search', 'submit', 'tel', 'text', 'time', 'url', 'week'];

        while (true) {
            $type = $io->choiceNoList(
                $this->trans('commands.generate.metabox.questions.field-type'),
                $fields_options,
                ''
            );


            $id = $io->ask(
                $this->trans('commands.generate.metabox.questions.field-id'),
                '',
                function ($id) use ($stringConverter) {
                    return $stringConverter->camelCaseToUnderscore($id);
                }
            );

            $label = $io->ask(
                $this->trans('commands.generate.metabox.questions.field-label'),
                ''
            );

            $description = $io->ask(
                $this->trans('commands.generate.metabox.questions.field-description'),
                ''
            );

            $field_placeholder = '';
            $default_value = '';
            if ($type != 'select' && $type != 'radio') {
                $field_placeholder = $io->ask(
                    $this->trans('commands.generate.metabox.questions.field-placeholder'),
                    ''
                );

                $default_value = $io->ask(
                    $this->trans('commands.generate.metabox.questions.field-default-value'),
                    ''
                );
            }

            $multi_selection = [];
            if ($type == 'select' || $type == 'radio') {
                if ($io->confirm(
                    $this->trans('commands.generate.metabox.questions.field-metabox-multiple-options', $type),
                    false
                )
                ) {
                    $multi_selection = $this->multiSelection($io, $type);
                }

            }

            array_push(
                $fields,
                [
                    'type' => $type,
                    'id' => $id,
                    'label' => $label,
                    'description' => $description,
                    'placeholder' => $field_placeholder,
                    'default_value' => $default_value,
                    'multi_selection' => $multi_selection
                ]
            );

            if (!$io->confirm(
                $this->trans('commands.generate.metabox.questions.field-metabox-add'),
                false
            )
            ) {
                break;
            }
        }

        return $fields;
    }

    private function multiSelection(WPStyle $io, $type)
    {
        $multiple_options = [];
        while (true) {
            $multiple_options_label = $io->ask(
                $this->trans('commands.generate.metabox.questions.multiple-options-label'),
                ''
            );


            $multiple_options_value = $io->ask(
                $this->trans('commands.generate.metabox.questions.multiple-options-value'),
                ''
            );

            array_push(
                $multiple_options,
                [
                    'label' => $multiple_options_label,
                    'value' => $multiple_options_value
                ]
            );
            if (!$io->confirm(
                $this->trans('commands.generate.metabox.questions.field-metabox-multiple-options-add', $type),
                false
            )
            ) {
                break;
            }
        }

        return $multiple_options;
    }
}

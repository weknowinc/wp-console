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
    use MetaboxTrait;
    
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
            /*   ->addOption(
                 'text-domain',
                 '',
                 InputOption::VALUE_REQUIRED,
                 $this->trans('commands.common.options.text-domain')
             )*/
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
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.metabox.options.fields-metabox')
            );
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
    
        $this->generator->generate(
            $plugin,
            $class_name,
            $metabox_id,
            $title,
            $callback_function,
            $screen,
            $page_location,
            $priority,
            $fields_metabox
        );
    }
    
    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);
    
        $validator = $this->validator;
    
        // --plugin
        $plugin = $input->getOption('plugin');
        if (!$plugin) {
            $plugin = $this->pluginQuestion($io);
            $input->setOption('plugin', $plugin);
        }
        /*
        // --class name
        $class_name = $input->getOption('class_name');
        if (!$class_name) {
            $class_name = $io->ask(
                $this->trans('commands.generate.metabox.questions.class-name'),
                ' '
            );
        }
        $input->setOption('class-name', $class_name);

        // --metabox id
        $metabox_id = $input->getOption('metabox-id');
        if (!$metabox_id) {
            $metabox_id = $io->ask(
                $this->trans('commands.generate.metabox.questions.metabox-id'),
                ' '
            );
        }
        $input->setOption('metabox-id', $metabox_id);

        // --function
        $title = $input->getOption('title');
        if (!$title) {
            $title = $io->ask(
                $this->trans('commands.generate.metabox.questions.title'),
                ''
            );
        }
        $input->setOption('title', $title);

        // --callback_function
         $callback_function = $input->getOption('callback-function');
         if (!$callback_function) {
             $callback_function = $io->ask(
                 $this->trans('commands.generate.metabox.questions.callback-function'),
                 ''
             );
             $input->setOption('callback-function', $callback_function);
         }

        // --screen
        $screen_options = ['normal', 'advance', 'side'];
        $screen = $input->getOption('screen');
        if (!$screen) {
            $screen = $io->choice(
                $this->trans('commands.generate.metabox.questions.screen'),
                $screen_options
            );
        }
        $input->setOption('screen', $screen);

        // --page location
        $page_location = $input->getOption('page-location');
         if (!$page_location) {
             $page_location = $io->ask(
                 $this->trans('commands.generate.metabox.questions.page-location'),
                 ''
             );
             $input->setOption('page-location', $page_location);
         }

        // --priority
        $priority = $input->getOption('priority');
        if (!$priority) {
            $priority = $io->ask(
                $this->trans('commands.generate.metabox.questions.priority'),
                ''
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
        }*/
    }
    
    /**
     * @return MetaBoxGenerator
     */
    protected function createGenerator()
    {
        return new MetaBoxGenerator();
    }
}

<?php

/**
 * @file
 * Contains \WP\Console\Command\Generate\SettingsPageCommand.
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
use WP\Console\Generator\SettingsPageGenerator;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Utils\Validator;
use WP\Console\Core\Utils\StringConverter;
use WP\Console\Utils\WordpressApi;

class SettingsPageCommand extends Command
{
    use PluginTrait;
    use ConfirmationTrait;
    use FieldsTypeTrait;

    /**
     * @var SettingsPageGenerator
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
     * @var WordpressApi
     */
    protected $wordpressApi;


    /**
     * SettingsPageCommand constructor.
     *
     * @param SettingsPageGenerator $generator
     * @param Manager               $extensionManager
     * @param Validator             $validator
     * @param StringConverter       $stringConverter
     * @param WordpressApi          $wordpressApi
     */
    public function __construct(
        SettingsPageGenerator $generator,
        Manager $extensionManager,
        Validator $validator,
        StringConverter $stringConverter,
        WordpressApi $wordpressApi
    ) {
        $this->generator = $generator;
        $this->extensionManager = $extensionManager;
        $this->validator = $validator;
        $this->stringConverter = $stringConverter;
        $this->wordpressApi = $wordpressApi;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:settings:page')
            ->setDescription($this->trans('commands.generate.settings.page.description'))
            ->setHelp($this->trans('commands.generate.settings.page.help'))
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
                $this->trans('commands.generate.settings.page.options.class-name')
            )
            ->addOption(
                'setting-group',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.settings.page.options.setting-group')
            )
            ->addOption(
                'setting-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.settings.page.options.setting-name')
            )
            ->addOption(
                'menu-title',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.settings.page.options.menu-title')
            )
            ->addOption(
                'capability',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.settings.page.options.user-capability')
            )
            ->addOption(
                'slug',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.settings.page.options.slug')
            )
            ->addOption(
                'callback-function',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.settings.page.options.callback-function')
            )
            ->addOption(
                'page-title',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.settings.page.options.page-title')
            )
            ->addOption(
                'sections',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.settings.page.options.section')
            )
            ->addOption(
                'fields',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.settings.page.options.fields')
            )
            ->addOption(
                'text-domain',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.settings.page.options.text-domain')
            )
            ->setAliases(['gsp']);
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
        $class_name = $this->validator->validateClassName($input->getOption('class-name'));
        $setting_group = $input->getOption('setting-group');
        $setting_name = $input->getOption('setting-name');
        $page_title = $input->getOption('page-title');
        $menu_title = $input->getOption('menu-title');
        $capability = $input->getOption('capability');
        $slug = $input->getOption('slug');
        $callback_function = $this->validator->validateFunctionName($input->getOption('callback-function'));
        $sections= $input->getOption('sections');
        $fields = $input->getOption('fields');
        $text_domain = $input->getOption('text-domain');

        $this->generator->generate(
            $plugin,
            $class_name,
            $setting_group,
            $setting_name,
            $page_title,
            $menu_title,
            $capability,
            $slug,
            $callback_function,
            $sections,
            $fields,
            $text_domain
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);

        // --plugin
        $plugin = $input->getOption('plugin');
        if (!$plugin) {
            $plugin = $this->pluginQuestion($io);
            $input->setOption('plugin', $plugin);
        }

        // --class name
        $class_name = $input->getOption('class-name');
        if (!$class_name) {
            $class_name = $io->ask(
                $this->trans('commands.generate.settings.page.questions.class-name'),
                'CustomSettingsPage',
                function ($value) {
                    if (!strlen(trim($value))) {
                        throw new \Exception('The Class name can not be empty');
                    }
                    return $this->stringConverter->humanToCamelCase($value);
                }
            );
            $input->setOption('class-name', $class_name);
        }

        // --setting group
        $setting_group = $input->getOption('setting-group');
        if (!$setting_group) {
            $setting_group = $io->ask(
                $this->trans('commands.generate.settings.page.questions.setting-group'),
                strtolower($class_name).'_group'
            );
        }
        $input->setOption('setting-group', $setting_group);

        // --setting name
        $setting_name = $input->getOption('setting-name');
        if (!$setting_name) {
            $setting_name = $io->ask(
                $this->trans('commands.generate.settings.page.questions.setting-name'),
                strtolower($class_name).'_name'
            );
        }
        $input->setOption('setting-name', $setting_name);

        // --capability
        $capability = $input->getOption('capability');
        if (!$capability) {
            $capability = $io->choiceNoList(
                $this->trans('commands.generate.settings.page.questions.capability'),
                $this->wordpressApi->getCapabilities(),
                "manage_options"
            );
        }
        $input->setOption('capability', $capability);

        // --slug
        $slug = $input->getOption('slug');
        if (!$slug) {
            $slug = $io->ask(
                $this->trans('commands.generate.settings.page.questions.slug'),
                $this->stringConverter->createMachineName($menu_title),
                function ($value) {
                    return $this->stringConverter->createMachineName($value);
                }
            );
        }
        $input->setOption('slug', $slug);


        // --callback function
        $callback_function = $input->getOption('callback-function');
        if (!$callback_function) {
            $callback_function = $io->ask(
                $this->trans('commands.generate.settings.page.questions.callback-function'),
                null,
                function ($function) {
                    return $this->validator->validateFunctionName($function);
                }
            );
        }
        $input->setOption('callback-function', $callback_function);

        // --menu title
        $menu_title = $input->getOption('menu-title');
        if (!$menu_title) {
            $menu_title = $io->ask(
                $this->trans('commands.generate.settings.page.questions.menu-title'),
                'Custom Setting Page'
            );
        }
        $input->setOption('menu-title', $menu_title);

        // --page title
        $page_title = $input->getOption('page-title');
        if (!$page_title) {
            $page_title = $io->ask(
                $this->trans('commands.generate.settings.page.questions.page-title'),
                $menu_title
            );
        }
        $input->setOption('page-title', $page_title);

        // --sections
        $sections = $input->getOption('sections');
        if (!$sections) {
            $sections = [];
            if ($io->confirm(
                $this->trans('commands.generate.settings.page.questions.section-add'),
                true
            )
            ) {
                $validate_menu = '';
                while (true) {
                    $name = $io->ask(
                        $this->trans('commands.generate.settings.page.questions.section-name'),
                        'My custom section settings',
                        function ($value) use ($validate_menu) {
                            if ($value == $validate_menu) {
                                throw new \Exception('The name already exist');
                            }
                            return $value;
                        }
                    );

                    if (empty($name)) {
                        break;
                    }

                    $validate_menu = $name;

                    $sections[$this->stringConverter->createMachineName($name)] = $name;

                    if (!$io->confirm(
                        $this->trans('commands.generate.settings.page.questions.section-add-another'),
                        false
                    )
                    ) {
                        break;
                    }
                }
            }
        }
        $input->setOption('sections', $sections);

        // --fields
        $fields = $input->getOption('fields');
        if (!empty($sections)) {
            if (!$fields) {
                if ($io->confirm(
                    $this->trans('commands.generate.settings.page.questions.fields.fields-add'),
                    true
                )
                ) {
                    // @see \WP\Console\Command\Shared\FieldsTypeTrait::fieldsQuestion
                    $fields = $this->fieldsQuestion($io, 'settings.page', 'fields', $sections);
                    $input->setOption('fields', $fields);
                }
            }
        }

        // --text domain
        $text_domain = $input->getOption('text-domain');
        if (!$text_domain) {
            $text_domain = $io->askEmpty(
                $this->trans('commands.generate.settings.page.questions.text-domain')
            );
        }
        $input->setOption('text-domain', $text_domain);
    }
}

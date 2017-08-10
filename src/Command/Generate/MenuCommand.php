<?php

/**
 * @file
 * Contains \WP\Console\Command\Generate\MenuCommand.
 */

namespace WP\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use WP\Console\Command\Shared\ContainerAwareCommandTrait;
use WP\Console\Command\Shared\PluginTrait;
use WP\Console\Command\Shared\ConfirmationTrait;
use WP\Console\Generator\MenuGenerator;
use WP\Console\Core\Utils\StringConverter;
use WP\Console\Extension\Manager;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Utils\Validator;

class MenuCommand extends Command
{
    use ContainerAwareCommandTrait;
    use ConfirmationTrait;
    use PluginTrait;

    /**
     * @var MenuGenerator
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
     * ToolbarCommand constructor.
     *
     * @param MenuGenerator   $generator
     * @param Manager         $extensionManager
     * @param Validator       $validator
     * @param StringConverter $stringConverter
     */
    public function __construct(
        MenuGenerator $generator,
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
            ->setName('generate:menu')
            ->setDescription($this->trans('commands.generate.menu.description'))
            ->setHelp($this->trans('commands.generate.menu.help'))
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
                $this->trans('commands.generate.menu.options.class-name')
            )
            ->addOption(
                'function-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.menu.options.function-name')
            )
            ->addOption(
                'menu-items',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.menu.options.menu-items')
            )
            ->addOption(
                'child-themes',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.menu.options.child-themes')
            )
            ->setAliases(['gm']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);

        $plugin = $input->getOption('plugin');
        $class_name = $input->getOption('class-name');
        $function_name = $input->getOption('function-name');
        $menu_items = $input->getOption('menu-items');
        $child_themes = $input->getOption('child-themes');
        $yes = $input->hasOption('yes')?$input->getOption('yes'):false;

        // @see use WP\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io, $yes)) {
            return;
        }

        $this->generator->generate(
            $plugin,
            $class_name,
            $function_name,
            $menu_items,
            $child_themes
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
                $this->trans('commands.generate.menu.questions.class-name'),
                'DefaultMenu',
                function ($class_name) {
                    return $this->validator->validateClassName($class_name);
                }
            );
            $input->setOption('class-name', $class_name);
        }

        // --function name
        $function_name = $input->getOption('function-name');
        if (!$function_name) {
            $function_name = $io->ask(
                $this->trans('commands.generate.menu.questions.function-name'),
                $this->stringConverter->camelCaseToUnderscore($class_name)
            );
            $input->setOption('function-name', $function_name);
        }

        // --menu items
        $menu_items = $input->getOption('menu-items');
        if (!$menu_items) {
            $array_menu_items = [];
            $stringConverter = $this->stringConverter;
            while (true) {
                $menu = $io->ask(
                    $this->trans('commands.generate.menu.questions.menu'),
                    '',
                    function ($menu) use ($stringConverter) {
                        return $stringConverter->humanToCamelCase($menu);
                    }
                );

                if (!empty($menu)) {
                    $description = $io->ask(
                        $this->trans('commands.generate.menu.questions.description'),
                        ''
                    );

                    array_push(
                        $array_menu_items,
                        [
                            'menu' => $stringConverter->camelCaseToUnderscore($menu),
                            'description' => $description,
                        ]
                    );
                }



                if (!$io->confirm(
                    $this->trans('commands.generate.menu.questions.menu-add'),
                    true
                )
                ) {
                    break;
                }
            }
            $input->setOption('menu-items', $array_menu_items);
        }

        // --child themes
        $child_themes = $input->getOption('child-themes');
        if (!$child_themes) {
            $child_themes = $io->confirm(
                $this->trans('commands.generate.menu.questions.child-themes'),
                false
            );
            $input->setOption('child-themes', $child_themes);
        }
    }
}

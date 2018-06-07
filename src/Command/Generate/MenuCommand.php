<?php

/**
 * @file
 * Contains \WP\Console\Command\Generate\MenuCommand.
 */

namespace WP\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use WP\Console\Command\Shared\PluginTrait;
use WP\Console\Command\Shared\ConfirmationTrait;
use WP\Console\Core\Command\Command;
use WP\Console\Generator\MenuGenerator;
use WP\Console\Core\Utils\StringConverter;
use WP\Console\Extension\Manager;
use WP\Console\Utils\Validator;

class MenuCommand extends Command
{
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
        $plugin = $input->getOption('plugin');
        $class_name = $this->validator->validateClassName($input->getOption('class-name'));
        $function_name = $this->validator->validateFunctionName($input->getOption('function-name'));
        $menu_items = $input->getOption('menu-items');
        $child_themes = $input->getOption('child-themes');

        // @see use WP\Console\Command\Shared\ConfirmationTrait::confirmOperation
        if (!$this->confirmOperation()) {
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
            $function_name = $this->getIo()->ask(
                $this->trans('commands.generate.menu.questions.function-name'),
                $this->stringConverter->camelCaseToUnderscore($class_name),
                function ($function_name) {
                    return $this->validator->validateFunctionName($function_name);
                }
            );
            $input->setOption('function-name', $function_name);
        }

        // --menu items
        $menu_items = $input->getOption('menu-items');
        if (!$menu_items) {
            $array_menu_items = [];
            $stringConverter = $this->stringConverter;
            while (true) {
                $name = $this->getIo()->ask(
                    $this->trans('commands.generate.menu.questions.menu-items.name'),
                    '',
                    function ($menu) use ($stringConverter) {
                        return $stringConverter->humanToCamelCase($menu);
                    }
                );

                if (!empty($menu)) {
                    $description = $this->getIo()->ask(
                        $this->trans('commands.generate.menu.questions.menu-items.description'),
                        ''
                    );

                    array_push(
                        $array_menu_items,
                        [
                            'name' => $stringConverter->camelCaseToUnderscore($name),
                            'description' => $description,
                        ]
                    );
                }



                if (!$this->getIo()->confirm(
                    $this->trans('commands.generate.menu.questions.menu-items.menu-add-another'),
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
            $child_themes = $this->getIo()->confirm(
                $this->trans('commands.generate.menu.questions.child-themes'),
                false
            );
            $input->setOption('child-themes', $child_themes);
        }
    }
}

<?php

/**
 * @file
 * Contains \WP\Console\Command\Generate\SidebarCommand.
 */

namespace WP\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use WP\Console\Command\Shared\ContainerAwareCommandTrait;
use WP\Console\Command\Shared\ThemeTrait;
use WP\Console\Command\Shared\ConfirmationTrait;
use WP\Console\Generator\SidebarGenerator;
use WP\Console\Core\Utils\StringConverter;
use WP\Console\Extension\Manager;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Utils\Site;
use WP\Console\Utils\Validator;

class SidebarCommand extends Command
{
    use ContainerAwareCommandTrait;
    use ConfirmationTrait;
    use ThemeTrait;

    /**
     * @var SidebarGenerator
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
     * @var Site
     */
    protected $site;

    /**
     * ToolbarCommand constructor.
     *
     * @param SidebarGenerator $generator
     * @param Manager          $extensionManager
     * @param Validator        $validator
     * @param StringConverter  $stringConverter
     * @param Site             $site
     */
    public function __construct(
        SidebarGenerator $generator,
        Manager $extensionManager,
        Validator $validator,
        StringConverter $stringConverter,
        Site $site
    ) {
        $this->generator = $generator;
        $this->extensionManager = $extensionManager;
        $this->validator = $validator;
        $this->stringConverter = $stringConverter;
        $this->site = $site;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:sidebar')
            ->setDescription($this->trans('commands.generate.sidebar.description'))
            ->setHelp($this->trans('commands.generate.sidebar.help'))
            ->addOption(
                'theme',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.theme')
            )

            ->addOption(
                'function-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.sidebar.options.function-name')
            )
            ->addOption(
                'sidebar-items',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.sidebar.options.sidebar-items')
            )
            ->addOption(
                'child-themes',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.sidebar.options.child-themes')
            )
            ->setAliases(['gsb']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);

        $theme = $input->getOption('theme');
        $function_name = $this->validator->validateFunctionName($input->getOption('function-name'));
        $sidebar_items = $input->getOption('sidebar-items');
        $child_themes = $input->getOption('child-themes');
        $yes = $input->hasOption('yes')?$input->getOption('yes'):false;

        // @see use WP\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io, $yes)) {
            return;
        }

        $this->generator->generate(
            $theme,
            $function_name,
            $sidebar_items,
            $child_themes,
            $this->site
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);

        // --theme
        $theme = $input->getOption('theme');
        if (!$theme) {
            $theme = $this->themeQuestion($io);
            $input->setOption('theme', $theme);
        }

        // --function name
        $function_name = $input->getOption('function-name');
        if (!$function_name) {
            $function_name = $io->ask(
                $this->trans('commands.generate.sidebar.questions.function-name'),
                'custom_sidebar',
                function ($function_name) {
                    return $this->validator->validateFunctionName($function_name);
                }
            );
            $input->setOption('function-name', $function_name);
        }

        // --sidebar items
        $sidebar_items = $input->getOption('sidebar-items');
        if (!$sidebar_items) {
            $array_sidebar_items = [];
            $stringConverter = $this->stringConverter;
            while (true) {
                $id = $io->ask(
                    $this->trans('commands.generate.sidebar.questions.id'),
                    str_replace("_", "-", $function_name),
                    function ($id) use ($stringConverter) {
                        $id = $stringConverter->humanToCamelCase($id);
                        return $stringConverter->camelCaseToLowerCase($id);
                    }
                );

                $class = $io->ask(
                    $this->trans('commands.generate.sidebar.questions.class'),
                    $function_name,
                    function ($class) use ($stringConverter) {
                        $class = $stringConverter->humanToCamelCase($class);
                        return $stringConverter->camelCaseToLowerCase($class);
                    }
                );

                $name = $io->ask(
                    $this->trans('commands.generate.sidebar.questions.name'),
                    'default sidebar'
                );

                $description = $io->ask(
                    $this->trans('commands.generate.sidebar.questions.description'),
                    'My first Sidebar'
                );

                array_push(
                    $array_sidebar_items,
                    [
                        'id' => $id,
                        'class' => $class,
                        'name' => $name,
                        'description' => $description,
                    ]
                );

                if (!$io->confirm(
                    $this->trans('commands.generate.sidebar.questions.sidebar-add'),
                    true
                )
                ) {
                    break;
                }
            }
            $input->setOption('sidebar-items', $array_sidebar_items);
        }

        // --child themes
        $child_themes = $input->getOption('child-themes');
        if (!$child_themes) {
            $child_themes = $io->confirm(
                $this->trans('commands.generate.sidebar.questions.child-themes'),
                false
            );
            $input->setOption('child-themes', $child_themes);
        }
    }
}

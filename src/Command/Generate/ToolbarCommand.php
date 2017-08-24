<?php

/**
 * @file
 * Contains \WP\Console\Command\Generate\ToolbarCommand.
 */

namespace WP\Console\Command\Generate;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use WP\Console\Command\Shared\ContainerAwareCommandTrait;
use WP\Console\Command\Shared\PluginTrait;
use WP\Console\Command\Shared\ConfirmationTrait;
use WP\Console\Core\Utils\StringConverter;
use WP\Console\Extension\Manager;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Generator\ToolbarGenerator;
use WP\Console\Utils\Validator;

class ToolbarCommand extends Command
{
    use ContainerAwareCommandTrait;
    use ConfirmationTrait;
    use PluginTrait;

    /**
     * @var ToolbarGenerator
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
     * @param ToolbarGenerator $generator
     * @param Manager          $extensionManager
     * @param Validator        $validator
     * @param StringConverter  $stringConverter
     */
    public function __construct(
        ToolbarGenerator $generator,
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
            ->setName('generate:toolbar')
            ->setDescription($this->trans('commands.generate.toolbar.description'))
            ->setHelp($this->trans('commands.generate.toolbar.help'))
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
                $this->trans('commands.generate.toolbar.options.class-name')
            )
            ->addOption(
                'menu-items',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.toolbar.options.menu')
            )
            ->setAliases(['gtb']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);

        $plugin = $input->getOption('plugin');
        $class_name = $input->getOption('class-name');
        $menu_items = $input->getOption('menu-items');
        $yes = $input->hasOption('yes')?$input->getOption('yes'):false;

        // @see use WP\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io, $yes)) {
            return;
        }

        $this->generator->generate(
            $plugin,
            $class_name,
            $menu_items
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
                $this->trans('commands.generate.toolbar.questions.class-name'),
                'DefaultToolbar',
                function ($class_name) {
                    return $this->validator->validateClassName($class_name);
                }
            );
            $input->setOption('class-name', $class_name);
        }

        // --menu
        $menu_items = $input->getOption('menu-items');
        if (!$menu_items) {
            $menu_items = [];
            while (true) {

                $toolbar_id = $io->ask($this->trans('commands.generate.toolbar.questions.id'));
                $parent_id = $io->ask($this->trans('commands.generate.toolbar.questions.parent'));
                $title = $io->ask($this->trans('commands.generate.toolbar.questions.title'));
                $href = $io->ask($this->trans('commands.generate.toolbar.questions.href'));
                $menu_group = $io->choice($this->trans('commands.generate.toolbar.questions.group'), ['No include', 'true', 'false']);

                $options =
                    [
                        'id'     => $toolbar_id,
                        'parent' => $parent_id,
                        'title'  => $title,
                        'href'   => $href,
                        'group'  => $menu_group,
                    ];

                $meta = [];
                if ($io->confirm($this->trans('commands.generate.toolbar.questions.meta'))) {
                    $meta_options = ['html', 'class', 'target', 'onclick', 'title', 'tabindex'];
                    foreach ($meta_options as $value) {
                        $ask = $io->askEmpty($this->trans('commands.generate.toolbar.questions.'.$value));
                        if (!empty($ask)) {
                            $meta[$value] = $ask;
                        }
                    }

                    if(!empty($meta)){
                        $options['meta'] = $meta;
                    }
                }


                if ($menu_group == 'No include') {
                    unset($options['group']);
                }

                array_push( $menu_items, $options );

                if (!$io->confirm(
                    $this->trans('commands.generate.toolbar.questions.menu-add'),
                    true
                )
                ) {
                    break;
                }
            }
        }
        $input->setOption('menu-items', $menu_items);
    }
}

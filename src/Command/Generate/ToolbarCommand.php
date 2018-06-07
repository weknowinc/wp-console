<?php

/**
 * @file
 * Contains \WP\Console\Command\Generate\ToolbarCommand.
 */

namespace WP\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use WP\Console\Command\Shared\PluginTrait;
use WP\Console\Command\Shared\ConfirmationTrait;
use WP\Console\Core\Command\Command;
use WP\Console\Core\Utils\StringConverter;
use WP\Console\Extension\Manager;
use WP\Console\Generator\ToolbarGenerator;
use WP\Console\Utils\Site;
use WP\Console\Utils\Validator;

class ToolbarCommand extends Command
{
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
     * @var Site
     */
    protected $site;

    /**
     * ToolbarCommand constructor.
     *
     * @param ToolbarGenerator $generator
     * @param Manager          $extensionManager
     * @param Validator        $validator
     * @param StringConverter  $stringConverter
     * @param Site             $site
     */
    public function __construct(
        ToolbarGenerator $generator,
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
                'function-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.toolbar.options.function-name')
            )
            ->addOption(
                'menu-items',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.toolbar.options.menu-items')
            )
            ->setAliases(['gtb']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $plugin = $input->getOption('plugin');
        $function_name = $this->validator->validateFunctionName($input->getOption('function-name'));
        $menu_items = $input->getOption('menu-items');

        // @see use WP\Console\Command\Shared\ConfirmationTrait::confirmOperation
        if (!$this->confirmOperation()) {
            return;
        }

        $this->generator->generate(
            $plugin,
            $function_name,
            $menu_items,
            $this->site
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

        // --function name
        $function_name = $input->getOption('function-name');
        if (!$function_name) {
            $function_name = $this->getIo()->ask(
                $this->trans('commands.generate.toolbar.questions.function-name'),
                'default_toolbar',
                function ($function_name) {
                    return $this->validator->validateFunctionName($function_name);
                }
            );
            $input->setOption('function-name', $function_name);
        }

        // --menu items
        $menu_items = $input->getOption('menu-items');
        if (!$menu_items) {
            $menu_items = [];
            while (true) {
                $toolbar_id = $this->getIo()->ask($this->trans('commands.generate.toolbar.questions.menu-items.id'));
                $parent_id = $this->getIo()->askEmpty($this->trans('commands.generate.toolbar.questions.menu-items.parent'));
                $title = $this->getIo()->ask($this->trans('commands.generate.toolbar.questions.menu-items.title'));
                $href = $this->getIo()->askEmpty($this->trans('commands.generate.toolbar.questions.menu-items.href'));
                $menu_group = $this->getIo()->choice($this->trans('commands.generate.toolbar.questions.menu-items.group'), ['No include', 'true', 'false']);

                $options =
                    [
                        'id'     => $toolbar_id,
                        'parent' => $parent_id,
                        'title'  => $title,
                        'href'   => $href,
                        'group'  => $menu_group,
                    ];

                $meta = [];
                if ($this->getIo()->confirm($this->trans('commands.generate.toolbar.questions.menu-items.meta-add'))) {
                    $meta_options = ['html', 'class', 'target', 'onclick', 'title', 'tabindex'];
                    foreach ($meta_options as $value) {
                        $ask = $this->getIo()->askEmpty($this->trans('commands.generate.toolbar.questions.menu-items.'.$value));
                        if (!empty($ask)) {
                            $meta[$value] = $ask;
                        }
                    }

                    if (!empty($meta)) {
                        $options['meta'] = $meta;
                    }
                }


                if ($menu_group == 'No include') {
                    unset($options['group']);
                }

                //Delete empty options
                foreach ($options as $key => $value) {
                    if (empty($value)) {
                        unset($options[$key]);
                    }
                }

                array_push($menu_items, $options);

                if (!$this->getIo()->confirm(
                    $this->trans('commands.generate.toolbar.questions.menu-items.menu-add-another'),
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

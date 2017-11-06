<?php

/**
 * @file
 * Contains \WP\Console\Command\Generate\RegisterStyleCommand.
 */

namespace WP\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use WP\Console\Command\Shared\FieldsTypeTrait;
use WP\Console\Command\Shared\PluginTrait;
use WP\Console\Command\Shared\ConfirmationTrait;
use WP\Console\Core\Command\Command;
use WP\Console\Generator\RegisterStyleGenerator;
use WP\Console\Core\Utils\StringConverter;
use WP\Console\Extension\Manager;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Utils\Site;
use WP\Console\Utils\Validator;

class RegisterStyleCommand extends Command
{
    use ConfirmationTrait;
    use PluginTrait;
    use FieldsTypeTrait;

    /**
     * @var RegisterStyleGenerator
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
     * RegisterStyleCommand constructor.
     *
     * @param RegisterStyleGenerator $generator
     * @param Manager                $extensionManager
     * @param Validator              $validator
     * @param StringConverter        $stringConverter
     * @param Site                   $site
     */
    public function __construct(
        RegisterStyleGenerator $generator,
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
            ->setName('generate:register:style')
            ->setDescription($this->trans('commands.generate.register.style.description'))
            ->setHelp($this->trans('commands.generate.register.style.help'))
            ->addOption(
                'plugin',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.plugin')
            )
            ->addOption(
                'function-name',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.function-name')
            )
            ->addOption(
                'hook',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.register.style.options.hook')
            )
            ->addOption(
                'styles-items',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.register.style.options.styles-items')
            )
            ->setAliases(['grs']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);

        $plugin = $input->getOption('plugin');
        $function_name = $this->validator->validateFunctionName($input->getOption('function-name'));
        $hook = $input->getOption('hook');
        $styles_items = $input->getOption('styles-items');
        $yes = $input->hasOption('yes')?$input->getOption('yes'):false;

        // @see use WP\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io, $yes)) {
            return;
        }

        $this->generator->generate(
            $plugin,
            $function_name,
            $hook,
            $styles_items,
            $this->site
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

        // --function name
        $function_name = $input->getOption('function-name');
        if (!$function_name) {
            $function_name = $io->ask(
                $this->trans('commands.generate.register.style.questions.function-name'),
                'custom_register_style',
                function ($function_name) {
                    return $this->validator->validateFunctionName($function_name);
                }
            );
            $input->setOption('function-name', $function_name);
        }

        // --hook
        $hook = $input->getOption('hook');
        if (!$hook) {
            $hook = $io->choice(
                $this->trans('commands.generate.register.style.questions.hook'),
                ['wp_enqueue_scripts', 'login_enqueue_scripts', 'admin_enqueue_scripts', 'enqueue_embed_scripts' ]
            );
        }
        $input->setOption('hook', $hook);

        // -- styles items
        $styles_items = $input->getOption('styles-items');
        if (!$styles_items) {
            $styles_items = [];
            while (true) {
                $name = $io->ask($this->trans('commands.generate.register.style.questions.styles-items.name'));
                $url = $io->ask($this->trans('commands.generate.register.style.questions.styles-items.url'));
                $dependencies = $io->askEmpty($this->trans('commands.generate.register.style.questions.styles-items.dependencies'));
                $version = $io->askEmpty($this->trans('commands.generate.register.style.questions.styles-items.version'));
                $media = $io->choiceNoList(
                    $this->trans('commands.generate.register.style.questions.styles-items.media'),
                    ["all", "braille", "embossed", "handheld", "print", "projection", "screen", "speech", "tty", "tv"]
                );
                $deregister = $io->confirm($this->trans('commands.generate.register.style.questions.styles-items.deregister'));
                $enqueuer = $io->confirm($this->trans('commands.generate.register.style.questions.styles-items.enqueue'));

                array_push(
                    $styles_items,
                    [
                        "name"  => $name,
                        "url" => $url,
                        "dependencies" => explode(",", str_replace(" ", "", $dependencies)),
                        "version" => $version,
                        "media" => $media,
                        "deregister" => $deregister,
                        "enqueue" => $enqueuer
                    ]
                );

                if (!$io->confirm(
                    $this->trans('commands.generate.register.style.questions.styles-items.styles-add-another'),
                    true
                )
                ) {
                    break;
                }
            }
        }
        $input->setOption('styles-items', $styles_items);
    }
}

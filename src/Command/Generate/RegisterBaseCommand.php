<?php

/**
 * @file
 * Contains WP\Console\Command\Generate\RegisterBaseCommand.
 */

namespace WP\Console\Command\Generate;

use WP\Console\Command\Shared\ConfirmationTrait;
use WP\Console\Command\Shared\ExtensionTrait;
use WP\Console\Extension\Manager;
use WP\Console\Generator\RegisterBaseGenerator;
use WP\Console\Utils\Site;
use WP\Console\Utils\Validator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WP\Console\Core\Command\ContainerAwareCommand;
use WP\Console\Core\Utils\StringConverter;

abstract class RegisterBaseCommand extends ContainerAwareCommand
{
    use ConfirmationTrait;
    use ExtensionTrait;

    private $RegisterType;
    private $commandName;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var RegisterBaseGenerator
     */
    protected $generator;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var Site
     */
    protected $site;

    /**
     * RegisterBaseCommand constructor.
     *
     * @param RegisterBaseGenerator $generator
     * @param Manager               $extensionManager
     * @param Validator             $validator
     * @param StringConverter       $stringConverter
     * @param Site                  $site
     */
    public function __construct(
        RegisterBaseGenerator $generator,
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

    protected function setRegisterType($RegisterBaseType)
    {
        return $this->RegisterType = $RegisterBaseType;
    }

    protected function setCommandName($commandName)
    {
        return $this->commandName = $commandName;
    }

    protected function configure()
    {
        $this
            ->setName($this->commandName)
            ->setDescription($this->trans('commands.generate.register.'.$this->RegisterType.'.description'))
            ->setHelp($this->trans('commands.generate.register.'.$this->RegisterType.'.help'))
            ->addOption(
                'extension',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.extension')
            )
            ->addOption(
                'extension-type',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.extension-type')
            )
            ->addOption(
                'function-name',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.register.'.$this->RegisterType.'.options.function-name')
            )
            ->addOption(
                'hook',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.register.'.$this->RegisterType.'.options.hook')
            )
            ->addOption(
                $this->RegisterType.'-items',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.register.'.$this->RegisterType.'.options.'.$this->RegisterType.'-items')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $extension = $input->getOption('extension');
        $extensionType = $input->getOption('extension-type');
        $function_name = $this->validator->validateFunctionName($input->getOption('function-name'));
        $hook = $input->getOption('hook');
        $register_items = $input->getOption($this->RegisterType.'-items');

        // @see use WP\Console\Command\Shared\ConfirmationTrait::confirmOperation
        if (!$this->confirmOperation()) {
            return;
        }

        $this->generator->generate(
            $extensionType,
            $extension,
            $this->RegisterType,
            $function_name,
            $hook,
            $register_items,
            $this->site
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // --extension type
        $extensionType = $input->getOption('extension-type');
        if (!$extensionType) {
            $extensionType = $this->extensionTypeQuestion();
            $input->setOption('extension-type', $extensionType);
        }

        // --extension
        $extension = $input->getOption('extension');
        if (!$extension) {
            $extension = $this->extensionQuestion($extensionType);
            $input->setOption('extension', $extension);
        }

        // --function name
        $function_name = $input->getOption('function-name');
        if (!$function_name) {
            $function_name = $this->getIo()->ask(
                $this->trans('commands.generate.register.'.$this->RegisterType.'.questions.function-name'),
                'custom_register_'.$this->RegisterType.'_'.$extensionType,
                function ($function_name) {
                    return $this->validator->validateFunctionName($function_name);
                }
            );
            $input->setOption('function-name', $function_name);
        }

        // --hook
        $hook = $input->getOption('hook');
        if (!$hook) {
            $hook = $this->getIo()->choice(
                $this->trans('commands.generate.register.'.$this->RegisterType.'.questions.hook'),
                ['wp_enqueue_scripts', 'login_enqueue_scripts', 'admin_enqueue_scripts', 'enqueue_embed_scripts' ]
            );
        }
        $input->setOption('hook', $hook);

        // -- styles or script items
        $register_items = $input->getOption($this->RegisterType.'-items');
        if (!$register_items) {
            $register_items = [];
            while (true) {
                $name = $this->getIo()->ask(
                    $this->trans('commands.generate.register.'.$this->RegisterType.'.questions.'.$this->RegisterType.'-items.name')
                );

                $url = $this->getIo()->ask(
                    $this->trans('commands.generate.register.'.$this->RegisterType.'.questions.'.$this->RegisterType.'-items.url')
                );

                $dependencies = $this->getIo()->askEmpty(
                    $this->trans('commands.generate.register.'.$this->RegisterType.'.questions.'.$this->RegisterType.'-items.dependencies')
                );

                $version = $this->getIo()->askEmpty(
                    $this->trans('commands.generate.register.'.$this->RegisterType.'.questions.'.$this->RegisterType.'-items.version')
                );

                $media = $this->getIo()->choiceNoList(
                    $this->trans('commands.generate.register.'.$this->RegisterType.'.questions.'.$this->RegisterType.'-items.'.($this->RegisterType == "script" ? "location":"media")),
                    $this->RegisterType == "script" ? ["header", "footer"]:["all", "braille", "embossed", "handheld", "print", "projection", "screen", "speech", "tty", "tv"]
                );

                $deregister = $this->getIo()->confirm(
                    $this->trans('commands.generate.register.'.$this->RegisterType.'.questions.'.$this->RegisterType.'-items.deregister'),
                    false
                );

                $enqueuer = $this->getIo()->confirm(
                    $this->trans('commands.generate.register.'.$this->RegisterType.'.questions.'.$this->RegisterType.'-items.enqueue')
                );

                $location = $this->RegisterType == "script" ? "location":"media";

                array_push(
                    $register_items,
                    [
                        "name"  => $name,
                        "url" => $url,
                        "dependencies" => explode(",", str_replace(" ", "", $dependencies)),
                        "version" => $version,
                        $location => $media,
                        "deregister" => $deregister,
                        "enqueue" => $enqueuer
                    ]
                );

                if (!$this->getIo()->confirm(
                    $this->trans('commands.generate.register.'.$this->RegisterType.'.questions.'.$this->RegisterType.'-items.'.$this->RegisterType.'-add-another'),
                    false
                )
                ) {
                    break;
                }
            }
        }
        $input->setOption($this->RegisterType.'-items', $register_items);
    }
}

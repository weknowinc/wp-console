<?php

/**
 * @file
 * Contains \WP\Console\Command\Generate\UserContactMethodsCommand.
 */

namespace WP\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use WP\Console\Command\Shared\PluginTrait;
use WP\Console\Command\Shared\ConfirmationTrait;
use WP\Console\Core\Command\Command;
use WP\Console\Generator\UserContactMethodsGenerator;
use WP\Console\Core\Utils\StringConverter;
use WP\Console\Extension\Manager;
use WP\Console\Utils\Site;
use WP\Console\Utils\Validator;

class UserContactMethodsCommand extends Command
{
    use ConfirmationTrait;
    use PluginTrait;

    /**
     * @var UserContactMethodsGenerator
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
     * UserContactMethodsCommand constructor.
     *
     * @param UserContactMethodsGenerator $generator
     * @param Manager                     $extensionManager
     * @param Validator                   $validator
     * @param StringConverter             $stringConverter
     * @param Site                        $site
     */
    public function __construct(
        UserContactMethodsGenerator $generator,
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
            ->setName('generate:user:contact:methods')
            ->setDescription($this->trans('commands.generate.user.contact.methods.description'))
            ->setHelp($this->trans('commands.generate.user.contact.methods.help'))
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
                'methods-items',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.user.contact.methods.options.user.contactmethods-items')
            )
            ->setAliases(['gucm']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $plugin = $input->getOption('plugin');
        $function_name = $this->validator->validatefunctionName($input->getOption('function-name'));
        $methods_items = $input->getOption('methods-items');

        // @see use WP\Console\Command\Shared\ConfirmationTrait::confirmOperation
        if (!$this->confirmOperation()) {
            return 1;
        }

        $this->generator->generate(
            [
                "plugin" => $plugin,
                "function_name" => $function_name,
                "methods_items" => $methods_items,
            ],
            $this->site
        );

        return 0;
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
                $this->trans('commands.generate.user.contact.methods.questions.function-name'),
                'default_user_contactmethods',
                function ($function_name) {
                    return $this->validator->validateFunctionName($function_name);
                }
            );
            $input->setOption('function-name', $function_name);
        }

        // --methods items
        $methods_items = $input->getOption('methods-items');
        if (!$methods_items) {
            $methods_items = [];
            while (true) {
                $name = $this->getIo()->ask($this->trans('commands.generate.user.contact.methods.questions.methods-items.name'));
                $description = $this->getIo()->ask($this->trans('commands.generate.user.contact.methods.questions.methods-items.description'));

                array_push(
                    $methods_items,
                    [
                        "name"  => $name,
                        "description" => $description
                    ]
                );

                if (!$this->getIo()->confirm(
                    $this->trans('commands.generate.user.contact.methods.questions.methods-items.methods-add-another'),
                    true
                )
                ) {
                    break;
                }
            }
        }
        $input->setOption('methods-items', $methods_items);
    }
}

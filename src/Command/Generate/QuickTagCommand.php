<?php

/**
 * @file
 * Contains \WP\Console\quicktag\Generate\QuickTagCommand.
 */

namespace WP\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use WP\Console\Command\Shared\ContainerAwareCommandTrait;
use WP\Console\Command\Shared\ExtensionTrait;
use WP\Console\Command\Shared\PluginTrait;
use WP\Console\Command\Shared\ServicesTrait;
use WP\Console\Command\Shared\ConfirmationTrait;
use WP\Console\Generator\QuickTagGenerator;
use WP\Console\Core\Utils\StringConverter;
use WP\Console\Extension\Manager;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Utils\Site;
use WP\Console\Utils\Validator;

class QuickTagCommand extends Command
{
    use ContainerAwareCommandTrait;
    use ConfirmationTrait;
    use PluginTrait;
    use ServicesTrait;
    use ExtensionTrait;

    /**
     * @var QuickTagGenerator
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
     * QuickTagCommand constructor.
     *
     * @param QuickTagGenerator $generator
     * @param Manager           $extensionManager
     * @param Validator         $validator
     * @param StringConverter   $stringConverter
     * @param Site              $site
     */
    public function __construct(
        QuickTagGenerator $generator,
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
            ->setName('generate:quicktag')
            ->setDescription($this->trans('commands.generate.quicktag.description'))
            ->setHelp($this->trans('commands.generate.quicktag.help'))
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
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.quicktag.options.function-name')
            )
            ->addOption(
                'quicktag-items',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.quicktag.options.quicktag-items')
            )
            ->setAliases(['gqt']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);

        $extension = $input->getOption('extension');
        $extensionType = $input->getOption('extension-type');
        $function_name = $this->validator->validateFunctionName($input->getOption('function-name'));
        $quicktag_items = $input->getOption('quicktag-items');
        $yes = $input->hasOption('yes')?$input->getOption('yes'):false;

        // @see use WP\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io, $yes)) {
            return;
        }

        $this->generator->generate(
            $extensionType,
            $extension,
            $function_name,
            $quicktag_items,
            $this->site
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);

        // --extension type
        $extensionType = $input->getOption('extension-type');
        if (!$extensionType) {
            $extensionType = $this->extensionTypeQuestion($io);
            $input->setOption('extension-type', $extensionType);
        }

        // --extension
        $extension = $input->getOption('extension');
        if (!$extension) {
            $extension = $this->extensionQuestion($io, $extensionType);
            $input->setOption('extension', $extension);
        }

        // --function name
        $function_name = $input->getOption('function-name');
        if (!$function_name) {
            $function_name = $io->ask(
                $this->trans('commands.generate.quicktag.questions.function-name'),
                'custom_quicktag',
                function ($function_name) {
                    return $this->validator->validateFunctionName($function_name);
                }
            );
            $input->setOption('function-name', $function_name);
        }

        // --quicktag items
        $quicktag_items = $input->getOption('quicktag-items');
        if (!$quicktag_items) {
            $array_quicktag_items = [];
            $stringConverter = $this->stringConverter;
            while (true) {
                $id = $io->ask(
                    $this->trans('commands.generate.quicktag.questions.id'),
                    '',
                    function ($id) use ($stringConverter) {
                        return $stringConverter->humanToCamelCase($id);
                    }
                );

                $display = $io->ask(
                    $this->trans('commands.generate.quicktag.questions.display'),
                    ''
                );

                $starting_tag = $io->ask(
                    $this->trans('commands.generate.quicktag.questions.starting-tag'),
                    ''
                );

                $ending_tag = $io->askEmpty(
                    $this->trans('commands.generate.quicktag.questions.ending-tag')
                );

                $key = $io->askEmpty(
                    $this->trans('commands.generate.quicktag.questions.key')
                );

                $title = $io->askEmpty(
                    $this->trans('commands.generate.quicktag.questions.title')
                );

                $priority = $io->askEmpty(
                    $this->trans('commands.generate.quicktag.questions.priority')
                );

                $instance = $io->askEmpty(
                    $this->trans('commands.generate.quicktag.questions.instance')
                );

                array_push(
                    $array_quicktag_items,
                    [
                        'id' => $id,
                        'display' => $display,
                        'starting-tag' => $starting_tag,
                        'ending-tag' => $ending_tag,
                        'key' => $key,
                        'title' => $title,
                        'priority' => $priority,
                        'instance' => $instance
                    ]
                );

                if (!$io->confirm(
                    $this->trans('commands.generate.quicktag.questions.quicktag-add'),
                    true
                )
                ) {
                    break;
                }
            }
            $input->setOption('quicktag-items', $array_quicktag_items);
        }
    }
}

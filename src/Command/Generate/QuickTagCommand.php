<?php

/**
 * @file
 * Contains \WP\ConsolCommand\Generate\QuickTagCommand.
 */

namespace WP\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use WP\Console\Command\Shared\ExtensionTrait;
use WP\Console\Command\Shared\PluginTrait;
use WP\Console\Command\Shared\ConfirmationTrait;
use WP\Console\Core\Command\Command;
use WP\Console\Generator\QuickTagGenerator;
use WP\Console\Core\Utils\StringConverter;
use WP\Console\Extension\Manager;
use WP\Console\Utils\Site;
use WP\Console\Utils\Validator;

class QuickTagCommand extends Command
{
    use ConfirmationTrait;
    use PluginTrait;
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
        $extension = $input->getOption('extension');
        $extensionType = $input->getOption('extension-type');
        $function_name = $this->validator->validateFunctionName($input->getOption('function-name'));
        $quicktag_items = $input->getOption('quicktag-items');

        // @see use WP\Console\Command\Shared\ConfirmationTrait::confirmOperation
        if (!$this->confirmOperation()) {
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
                $id = $this->getIo()->ask(
                    $this->trans('commands.generate.quicktag.questions.quicktag-items.id'),
                    '',
                    function ($id) use ($stringConverter) {
                        return $stringConverter->humanToCamelCase($id);
                    }
                );

                $display = $this->getIo()->ask(
                    $this->trans('commands.generate.quicktag.questions.quicktag-items.display'),
                    ''
                );

                $starting_tag = $this->getIo()->ask(
                    $this->trans('commands.generate.quicktag.questions.quicktag-items.starting-tag'),
                    ''
                );

                $ending_tag = $this->getIo()->askEmpty(
                    $this->trans('commands.generate.quicktag.questions.quicktag-items.ending-tag')
                );

                $key = $this->getIo()->askEmpty(
                    $this->trans('commands.generate.quicktag.questions.quicktag-items.key')
                );

                $title = $this->getIo()->askEmpty(
                    $this->trans('commands.generate.quicktag.questions.quicktag-items.title')
                );

                $priority = $this->getIo()->askEmpty(
                    $this->trans('commands.generate.quicktag.questions.quicktag-items.priority'),
                    function ($priority) {
                        if (!is_numeric(trim($priority))) {
                            throw new \Exception('The Priority only can be a number');
                        }
                        return $priority;
                    }
                );

                $instance = $this->getIo()->askEmpty(
                    $this->trans('commands.generate.quicktag.questions.quicktag-items.instance')
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

                if (!$this->getIo()->confirm(
                    $this->trans('commands.generate.quicktag.questions.quicktag-items.quicktag-add-another'),
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

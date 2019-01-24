<?php

namespace WP\Console\Core;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use WP\Console\Core\Command\Chain\ChainCustomCommand;
use WP\Console\Core\EventSubscriber\SendStatisticsListener;
use WP\Console\Core\EventSubscriber\CallCommandListener;
use WP\Console\Core\EventSubscriber\SaveStatisticsListener;
use WP\Console\Core\EventSubscriber\ShowGenerateChainListener;
use WP\Console\Core\EventSubscriber\ShowGenerateCountCodeLinesListener;
use WP\Console\Core\EventSubscriber\ShowGeneratedFilesListener;
use WP\Console\Core\EventSubscriber\ShowGenerateInlineListener;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Core\Utils\ConfigurationManager;
use WP\Console\Core\Utils\ChainDiscovery;

/**
 * Class Application
 *
 * @package WP\Console
 */
class Application extends BaseApplication
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var string
     */
    protected $commandName;

    /**
     * @var bool
     */
    protected $eventRegistered;

    /**
     * ConsoleApplication constructor.
     *
     * @param ContainerInterface $container
     * @param string             $name
     * @param string             $version
     */
    public function __construct(
        ContainerInterface $container,
        $name,
        $version
    ) {
        $this->container = $container;
        $this->eventRegistered = false;
        parent::__construct($name, $version);
        $this->addOptions();
    }

    public function getTranslator()
    {
        if ($this->container) {
            return $this->container->get('console.translator_manager');
        }

        return null;
    }

    /**
     * @param $key string
     *
     * @return string
     */
    public function trans($key)
    {
        if ($this->getTranslator()) {
            return $this->getTranslator()->trans($key);
        }

        return null;
    }

    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);
        $messageManager = $this->container->get('console.message_manager');
        $this->commandName = $this->getCommandName($input)?:'list';

        $clear = $this->container->get('console.configuration_manager')
            ->getConfiguration()
            ->get('application.clear')?:false;
        if ($clear === true || $clear === 'true') {
            $output->write(sprintf("\033\143"));
        }

        $this->loadCommands();

        /**
         * @var ConfigurationManager $configurationManager
         */
        $configurationManager = $this->container
            ->get('console.configuration_manager');

        if (!$this->has($this->commandName)) {
            $isValidCommand = false;
            $config = $configurationManager->getConfiguration();
            $mappings = $config->get('application.commands.mappings');

            if (array_key_exists($this->commandName, $mappings)) {
                $commandNameMap = $mappings[$this->commandName];
                $messageManager->warning(
                    sprintf(
                        $this->trans('application.errors.renamed-command'),
                        $this->commandName,
                        $commandNameMap
                    )
                );
                $this->add(
                    $this->find($commandNameMap)->setAliases([$this->commandName])
                );
                $isValidCommand = true;
            }


            $namespaces = $this->getNamespaces();
            if (in_array($this->commandName, $namespaces)) {
                $input = new ArrayInput(
                    [
                        'command' => 'list',
                        'namespace' => $this->commandName
                    ]
                );
                $this->commandName = 'list';
                $isValidCommand = true;
            }

            if (!$isValidCommand) {
                $io->error(
                    sprintf(
                        $this->trans('application.errors.invalid-command'),
                        $this->commandName
                    )
                );

                return 1;
            }
        }

        $code = parent::doRun(
            $input,
            $output
        );

        //        // Propagate Drupal messages.
        //        $this->addDrupalMessages($messageManager);

        if ($this->showMessages($input)) {
            $messages = $messageManager->getMessages();

            foreach ($messages as $message) {
                $showBy = $message['showBy'];
                if ($showBy!=='all' && $showBy!==$this->commandName) {
                    continue;
                }
                $type = $message['type'];
                $io->$type($message['message']);
            }
        }


        return $code;
    }

    public function loadCommands()
    {
        $this->registerGenerators();
        $this->registerCommands();
        $this->registerEvents();
        $this->registerExtendCommands();

        /**
         * @var ConfigurationManager $configurationManager
         */
        $configurationManager = $this->container
            ->get('console.configuration_manager');

        $config = $configurationManager->getConfiguration()
            ->get('application.extras.config')?:'true';
        if ($config === 'true') {
            $this->registerCommandsFromAutoWireConfiguration();
        }

        $chains = $configurationManager->getConfiguration()
            ->get('application.extras.chains')?:'true';
        if ($chains === 'true') {
            $this->registerChainCommands();
        }
    }

    /**
     * @param InputInterface $input
     *
     * @return bool
     */
    private function showMessages(InputInterface $input)
    {
        $format = $input->hasOption('format')?$input->getOption('format'):'txt';

        if ($format !== 'txt') {
            return false;
        }

        return true;
    }

    /**
     * registerCommands
     */
    private function registerCommands()
    {
        $consoleCommands = $this->container
            ->findTaggedServiceIds('wordpress.command');

        $aliases = $this->container->get('console.configuration_manager')
            ->getConfiguration()
            ->get('application.commands.aliases')?:[];

        $invalidCommands = [];
        if ($this->container->has('console.key_value_storage')) {
            $invalidCommands = $this->container
                ->get('console.key_value_storage')
                ->get('invalid_commands', []);
        }

        foreach ($consoleCommands as $name => $tags) {
            if (in_array($name, $invalidCommands)) {
                continue;
            }

            if (!$this->container->has($name)) {
                continue;
            }

            try {
                $command = $this->container->get($name);
            } catch (\Exception $e) {
                echo $name . ' - ' . $e->getMessage() . PHP_EOL;

                continue;
            }

            if (!$command) {
                continue;
            }

            if (method_exists($command, 'setTranslator')) {
                $command->setTranslator(
                    $this->container->get('console.translator_manager')
                );
            }

            if (method_exists($command, 'setContainer')) {
                $command->setContainer(
                    $this->container->get('service_container')
                );
            }

            if (array_key_exists($command->getName(), $aliases)) {
                $commandAliases = $aliases[$command->getName()];
                if (!is_array($commandAliases)) {
                    $commandAliases = [$commandAliases];
                }
                $commandAliases = array_merge(
                    $command->getAliases(),
                    $commandAliases
                );
                $command->setAliases($commandAliases);
            }

            $this->add($command);
        }
    }

    /**
     * registerGenerators
     */
    private function registerGenerators()
    {
        $consoleGenerators = $this->container
            ->findTaggedServiceIds('wordpress.generator');

        foreach ($consoleGenerators as $name => $tags) {
            if (!$this->container->has($name)) {
                continue;
            }

            try {
                $generator = $this->container->get($name);
            } catch (\Exception $e) {
                echo $name . ' - ' . $e->getMessage() . PHP_EOL;

                continue;
            }

            if (!$generator) {
                continue;
            }

            if (method_exists($generator, 'setRenderer')) {
                $generator->setRenderer(
                    $this->container->get('console.renderer')
                );
            }

            if (method_exists($generator, 'setFileQueue')) {
                $generator->setFileQueue(
                    $this->container->get('console.file_queue')
                );
            }

            if (method_exists($generator, 'setCountCodeLines')) {
                $generator->setCountCodeLines(
                    $this->container->get('console.count_code_lines')
                );
            }
        }
    }

    private function registerEvents()
    {
        if (!$this->eventRegistered) {
            $dispatcher = new EventDispatcher();
            /* @todo Register listeners as services */
            /*$dispatcher->addSubscriber(
                new ValidateExecutionListener(
                    $this->container->get('console.translator_manager'),
                    $this->container->get('console.configuration_manager')
                )
            );
            $dispatcher->addSubscriber(
                new ShowWelcomeMessageListener(
                    $this->container->get('console.translator_manager')
                )
            );
            $dispatcher->addSubscriber(
                new DefaultValueEventListener(
                    $this->container->get('console.configuration_manager')
                )
            );*/
            $dispatcher->addSubscriber(
                new CallCommandListener(
                    $this->container->get('console.chain_queue')
                )
            );
            $dispatcher->addSubscriber(
                new ShowGeneratedFilesListener(
                    $this->container->get('console.file_queue'),
                    $this->container->get('console.show_file')
                )
            );
            $dispatcher->addSubscriber(
                new ShowGenerateInlineListener(
                    $this->container->get('console.translator_manager')
                )
            );
            $dispatcher->addSubscriber(
                new ShowGenerateChainListener(
                    $this->container->get('console.translator_manager')
                )
            );
            $dispatcher->addSubscriber(
                new ShowGenerateCountCodeLinesListener(
                    $this->container->get('console.translator_manager'),
                    $this->container->get('console.count_code_lines')
                )
            );
            $dispatcher->addSubscriber(
                new SaveStatisticsListener(
                    $this->container->get('console.count_code_lines'),
                    $this->container->get('console.configuration_manager'),
                    $this->container->get('console.translator_manager')
                )
            );
            $dispatcher->addSubscriber(
                new SendStatisticsListener(
                    $this->container->get('console.configuration_manager'),
                    $this->container->get('console.translator_manager')
                )
            );

            $this->setDispatcher($dispatcher);
            $this->eventRegistered = true;
        }
    }

    private function addOptions()
    {
        $this->getDefinition()->addOption(
            new InputOption(
                '--env',
                '-e',
                InputOption::VALUE_OPTIONAL,
                $this->trans('application.options.env'), 'prod'
            )
        );
        $this->getDefinition()->addOption(
            new InputOption(
                '--root',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('application.options.root')
            )
        );
        $this->getDefinition()->addOption(
            new InputOption(
                '--no-debug',
                null,
                InputOption::VALUE_NONE,
                $this->trans('application.options.no-debug')
            )
        );
        $this->getDefinition()->addOption(
            new InputOption(
                '--learning',
                null,
                InputOption::VALUE_NONE,
                $this->trans('application.options.learning')
            )
        );
        $this->getDefinition()->addOption(
            new InputOption(
                '--generate-chain',
                '-c',
                InputOption::VALUE_NONE,
                $this->trans('application.options.generate-chain')
            )
        );
        $this->getDefinition()->addOption(
            new InputOption(
                '--generate-inline',
                '-i',
                InputOption::VALUE_NONE,
                $this->trans('application.options.generate-inline')
            )
        );
        $this->getDefinition()->addOption(
            new InputOption(
                '--generate-doc',
                '-d',
                InputOption::VALUE_NONE,
                $this->trans('application.options.generate-doc')
            )
        );
        $this->getDefinition()->addOption(
            new InputOption(
                '--target',
                '-t',
                InputOption::VALUE_OPTIONAL,
                $this->trans('application.options.target')
            )
        );
        $this->getDefinition()->addOption(
            new InputOption(
                '--uri',
                '-l',
                InputOption::VALUE_REQUIRED,
                $this->trans('application.options.uri')
            )
        );
        $this->getDefinition()->addOption(
            new InputOption(
                '--yes',
                '-y',
                InputOption::VALUE_NONE,
                $this->trans('application.options.yes')
            )
        );
        $this->getDefinition()->addOption(
            new InputOption(
                '--wp-content-dir',
                '-wcd',
                InputOption::VALUE_OPTIONAL,
                $this->trans('application.options.wp-content-dir')
            )
        );
    }

    private function registerCommandsFromAutoWireConfiguration()
    {
        /*$configuration = $this->container->get('console.configuration_manager')
            ->getConfiguration();

        $autoWireForcedCommands = $configuration
            ->get('application.autowire.commands.forced');

        foreach ($autoWireForcedCommands as $autoWireForcedCommand) {
            try {
                if (!$autoWireForcedCommand['class']) {
                    continue;
                }

                $reflectionClass = new \ReflectionClass(
                    $autoWireForcedCommand['class']
                );

                $arguments = [];
                if (array_key_exists('arguments', $autoWireForcedCommand)) {
                    foreach ($autoWireForcedCommand['arguments'] as $argument) {
                        $argument = substr($argument, 1);
                        $arguments[] = $this->container->get($argument);
                    }
                }

                $command = $reflectionClass->newInstanceArgs($arguments);

                if (method_exists($command, 'setTranslator')) {
                    $command->setTranslator(
                        $this->container->get('console.translator_manager')
                    );
                }
                if (method_exists($command, 'setContainer')) {
                    $command->setContainer(
                        $this->container->get('service_container')
                    );
                }

                $this->add($command);
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
                continue;
            }
        }

        $autoWireNameCommand = $configuration->get(
            sprintf(
                'application.autowire.commands.name.%s',
                $this->commandName
            )
        );

        if ($autoWireNameCommand) {
            try {
                $arguments = [];
                if (array_key_exists('arguments', $autoWireNameCommand)) {
                    foreach ($autoWireNameCommand['arguments'] as $argument) {
                        $argument = substr($argument, 1);
                        $arguments[] = $this->container->get($argument);
                    }
                }

                $reflectionClass = new \ReflectionClass(
                    $autoWireNameCommand['class']
                );
                $command = $reflectionClass->newInstanceArgs($arguments);

                if (method_exists($command, 'setTranslator')) {
                    $command->setTranslator(
                        $this->container->get('console.translator_manager')
                    );
                }
                if (method_exists($command, 'setContainer')) {
                    $command->setContainer(
                        $this->container->get('service_container')
                    );
                }

                $this->add($command);
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
        } */
    }

    public function registerChainCommands()
    {
        /**
         * @var ChainDiscovery $chainDiscovery
         */
        $chainDiscovery = $this->container->get('console.chain_discovery');
        $chainCommands = $chainDiscovery->getChainCommands();

        foreach ($chainCommands as $name => $chainCommand) {
            try {
                $file = $chainCommand['file'];
                $description = $chainCommand['description'];
                $command = new ChainCustomCommand(
                    $name,
                    $description,
                    $file,
                    $chainDiscovery
                );
                $this->add($command);
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
        }
    }

    /**
     * registerExtendCommands
     */
    private function registerExtendCommands()
    {
        $this->container->get('console.configuration_manager')
            ->loadExtendConfiguration();
    }


    public function getData($filterNamespaces = null, $excludeNamespaces = [], $excludeChainCommands = false)
    {
        $singleCommands = [
            'about',
            'chain',
            'check',
            'exec',
            'help',
            'init',
            'list',
            'server',
            'snippet'
        ];

        $data = [];
        // Exclude misc if it is inside the $excludeNamespaces array.
        if (!in_array('misc', $excludeNamespaces)) {
            foreach ($singleCommands as $singleCommand) {
                $data['commands']['misc'][] = $this->commandData($singleCommand);
            }
        }


        $namespaces = array_filter(
            $this->getNamespaces(), function ($item) {
                return (strpos($item, ':')<=0);
            }
        );
        sort($namespaces);
        array_unshift($namespaces, 'misc');

        // Exclude specific namespaces
        $namespaces = array_diff($namespaces, $excludeNamespaces);

        // filter namespaces if available
        if ($filterNamespaces) {
            $namespaces = array_intersect($namespaces, $filterNamespaces);
        }

        foreach ($namespaces as $namespace) {
            $commands = $this->all($namespace);
            usort(
                $commands, function ($cmd1, $cmd2) {
                    return strcmp($cmd1->getName(), $cmd2->getName());
                }
            );

            foreach ($commands as $command) {
                // Exclude command if is a chain command and was requested to exclude chain commands
                if ($excludeChainCommands && $command instanceof ChainCustomCommand) {
                    continue;
                }

                if (method_exists($command, 'getModule')) {
                    if ($command->getModule() == 'Console') {
                        $data['commands'][$namespace][] = $this->commandData(
                            $command->getName()
                        );
                    }
                } else {
                    $data['commands'][$namespace][] = $this->commandData(
                        $command->getName()
                    );
                }
            }
        }

        // Remove namepsaces without commands
        $namespaces = array_filter(
            $namespaces, function ($namespace) use ($data) {
                return count($data['commands'][$namespace]) > 0;
            }
        );

        $input = $this->getDefinition();
        $options = [];
        foreach ($input->getOptions() as $option) {
            $options[] = [
                'name' => $option->getName(),
                'description' => $this->trans('application.options.'.$option->getName())
            ];
        }
        $arguments = [];
        foreach ($input->getArguments() as $argument) {
            $arguments[] = [
                'name' => $argument->getName(),
                'description' => $this->trans('application.arguments.'.$argument->getName())
            ];
        }

        // Exclude misc if it is inside the $excludeNamespaces array.
        if (!in_array('misc', $excludeNamespaces)) {
            $data['application'] = [
                'namespaces' => $namespaces,
                'options' => $options,
                'arguments' => $arguments,
                'messages' => [
                    'title' =>  $this->trans('commands.generate.doc.gitbook.messages.title'),
                    'note' =>  $this->trans('commands.generate.doc.gitbook.messages.note'),
                    'note_description' =>  $this->trans('commands.generate.doc.gitbook.messages.note-description'),
                    'command' =>  $this->trans('commands.generate.doc.gitbook.messages.command'),
                    'options' => $this->trans('commands.generate.doc.gitbook.messages.options'),
                    'option' => $this->trans('commands.generate.doc.gitbook.messages.option'),
                    'details' => $this->trans('commands.generate.doc.gitbook.messages.details'),
                    'arguments' => $this->trans('commands.generate.doc.gitbook.messages.arguments'),
                    'argument' => $this->trans('commands.generate.doc.gitbook.messages.argument'),
                    'examples' => $this->trans('commands.generate.doc.gitbook.messages.examples')
                ],
                'examples' => []
            ];
        }
        return $data;
    }

    private function commandData($commandName)
    {
        if (!$this->has($commandName)) {
            return [];
        }

        $command = $this->find($commandName);

        $input = $command->getDefinition();
        $options = [];
        foreach ($input->getOptions() as $option) {
            $options[$option->getName()] = [
                'name' => $option->getName(),
                'description' => $this->trans($option->getDescription()),
            ];
        }

        $arguments = [];
        foreach ($input->getArguments() as $argument) {
            $arguments[$argument->getName()] = [
                'name' => $argument->getName(),
                'description' => $this->trans($argument->getDescription()),
            ];
        }

        $commandKey = str_replace(':', '.', $command->getName());

        $examples = [];
        for ($i = 0; $i < 5; $i++) {
            $description = sprintf(
                'commands.%s.examples.%s.description',
                $commandKey,
                $i
            );
            $execution = sprintf(
                'commands.%s.examples.%s.execution',
                $commandKey,
                $i
            );

            if ($description != $this->trans($description)) {
                $examples[] = [
                    'description' => $this->trans($description),
                    'execution' => $this->trans($execution)
                ];
            } else {
                break;
            }
        }

        $data = [
            'name' => $command->getName(),
            'description' => $command->getDescription(),
            'options' => $options,
            'arguments' => $arguments,
            'examples' => $examples,
            'aliases' => $command->getAliases(),
            'key' => $commandKey,
            'dashed' => str_replace(':', '-', $command->getName()),
            'messages' => [
                'usage' =>  $this->trans('commands.generate.doc.gitbook.messages.usage'),
                'options' => $this->trans('commands.generate.doc.gitbook.messages.options'),
                'option' => $this->trans('commands.generate.doc.gitbook.messages.option'),
                'details' => $this->trans('commands.generate.doc.gitbook.messages.details'),
                'arguments' => $this->trans('commands.generate.doc.gitbook.messages.arguments'),
                'argument' => $this->trans('commands.generate.doc.gitbook.messages.argument'),
                'examples' => $this->trans('commands.generate.doc.gitbook.messages.examples')
            ],
        ];

        return $data;
    }


    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * Finds a command by name or alias.
     *
     * @param string $name A command name or a command alias
     *
     * @return mixed A Command instance
     *
     * Override parent find method to avoid name collisions with automatically
     * generated command abbreviations.
     * Command name validation was previously done at doRun method.
     */
    public function find($name)
    {
        return $this->get($name);
    }
}

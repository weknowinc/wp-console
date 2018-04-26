<?php

namespace WP\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use WP\Console\Annotations\WPCommandAnnotationReader;
use WP\Console\Core\Command\Chain\ChainCustomCommand;
use WP\Console\Utils\AnnotationValidator;
use WP\Console\Core\Application as BaseApplication;

/**
 * Class Application
 *
 * @package WP\Console
 */
class Application extends BaseApplication
{
    /**
     * @var string
     */
    const NAME = 'WP Console';

    /**
     * @var string
     */
    const VERSION = '0.4.0';

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container, $this::NAME, $this::VERSION);
    }

    /**
     * {@inheritdoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->registerGenerators();
        $this->registerCommands();
        $this->registerExtendCommands();
        parent::doRun($input, $output);
    }

    private function registerGenerators()
    {
        if ($this->container->hasParameter('wordpress.generators')) {
            $consoleGenerators = $this->container->getParameter(
                'wordpress.generators'
            );
        } else {
            $consoleGenerators = array_keys(
                $this->container->findTaggedServiceIds('wordpress.generator')
            );
        }

        foreach ($consoleGenerators as $name) {
            if (!$this->container->has($name)) {
                continue;
            }

            $generator = $this->container->get($name);

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

    /**
     * registerExtendCommands
     */
    private function registerExtendCommands()
    {
        $this->container->get('console.configuration_manager')
            ->loadExtendConfiguration();
    }

    private function registerCommands()
    {
        if ($this->container->hasParameter('wordpress.commands')) {
            $consoleCommands = $this->container->getParameter(
                'wordpress.commands'
            );
        } else {
            $consoleCommands = array_keys(
                $this->container->findTaggedServiceIds('wordpress.command')
            );
            $this->container->setParameter(
                'console.warning',
                'application.site.errors.settings'
            );
        }

        $serviceDefinitions = [];
        $annotationValidator = null;
        $annotationCommandReader = null;
        if ($this->container->hasParameter('console.service_definitions')) {
            $serviceDefinitions = $this->container
                ->getParameter('console.service_definitions');

            /**
             * @var WPCommandAnnotationReader $annotationCommandReader
             */
            $annotationCommandReader = $this->container
                ->get('console.annotation_command_reader');

            /**
             * @var AnnotationValidator $annotationValidator
             */
            $annotationValidator = $this->container
                ->get('console.annotation_validator');
        }

        $aliases = $this->container->get('console.configuration_manager')
            ->getConfiguration()
            ->get('application.commands.aliases')?:[];
        foreach ($consoleCommands as $name) {
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

            if ($annotationValidator && $annotationCommandReader) {
                if (!$serviceDefinition = $serviceDefinitions[$name]) {
                    continue;
                }

                $annotation = $annotationCommandReader->readAnnotation($serviceDefinition->getClass());
                if ($annotation) {
                    $this->container->get('console.translator_manager')
                        ->addResourceTranslationsByExtension(
                            $annotation['extension'],
                            $annotation['extensionType']
                        );
                }

                if (!$annotationValidator->isValidCommand($serviceDefinition->getClass())) {
                    continue;
                }
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
                $commandAliases = array_unique(array_merge(
                    $command->getAliases()?$command->getAliases():[],
                    array_key_exists($command->getName(), $aliases)?$aliases[$command->getName()]:[]
                ));
                if (!is_array($commandAliases)) {
                    $commandAliases = [$commandAliases];
                }
                $command->setAliases($commandAliases);
            }

            $this->add($command);
        }
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
            'server'
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
        if($filterNamespaces) $namespaces = array_intersect($namespaces, $filterNamespaces);

        foreach ($namespaces as $namespace) {
            $commands = $this->all($namespace);
            usort(
                $commands, function ($cmd1, $cmd2) {
                return strcmp($cmd1->getName(), $cmd2->getName());
            }
            );

            foreach ($commands as $command) {
                // Exclude command if is a chain command and was requested to exclude chain commands
                if($excludeChainCommands && $command instanceof ChainCustomCommand) {
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
        $namespaces = array_filter($namespaces, function($namespace) use( $data) {
            return count($data['commands'][$namespace]) > 0;
        });

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
        $this->registerGenerators();
        $this->registerCommands();
    }
}

<?php

namespace WP\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use WP\Console\Annotations\WPCommandAnnotationReader;
use WP\Console\Utils\AnnotationValidator;
use WP\Console\Core\Application as BaseApplication;

use Doctrine\Common\Annotations\AnnotationRegistry;

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
        $this->validateCommands();
        parent::doRun($input, $output);
    }

    public function validateCommands()
    {
        $consoleCommands = $this->container
            ->findTaggedServiceIds('wordpress.command');

        if (!$consoleCommands) {
            return;
        }

        $serviceDefinitions = $this->container->getDefinitions();

        if (!$serviceDefinitions) {
            return;
        }

        if (!$this->container->has('console.annotation_command_reader')) {
            return;
        }

        /**
         * @var WPCommandAnnotationReader $annotationCommandReader
         */
        $annotationCommandReader = $this->container
            ->get('console.annotation_command_reader');

        if (!$this->container->has('console.annotation_validator')) {
            return;
        }

        /**
         * @var AnnotationValidator $annotationValidator
         */
        $annotationValidator = $this->container
            ->get('console.annotation_validator');

        $invalidCommands = [];

        foreach ($consoleCommands as $name => $tags) {
            AnnotationRegistry::reset();
            AnnotationRegistry::registerLoader(
                [
                    $this->container->get('class_loader'),
                    "loadClass"
                ]
            );

            if (!$this->container->has($name)) {
                $invalidCommands[] = $name;
                continue;
            }

            if (!$serviceDefinition = $serviceDefinitions[$name]) {
                $invalidCommands[] = $name;
                continue;
            }

            if (!$annotationValidator->isValidCommand(
                $serviceDefinition->getClass()
            )
            ) {
                $invalidCommands[] = $name;
                continue;
            }

            $annotation = $annotationCommandReader
                ->readAnnotation($serviceDefinition->getClass());
            if ($annotation) {
                $this->container->get('console.translator_manager')
                    ->addResourceTranslationsByExtension(
                        $annotation['extension'],
                        $annotation['extensionType']
                    );
            }
        }

        $this->container
            ->get('console.key_value_storage')
            ->set('invalid_commands', $invalidCommands);

        return;
    }
}

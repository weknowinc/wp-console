<?php

namespace WP\Console\Bootstrap;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use WP\Console\Core\DependencyInjection\ServiceModifierInterface;
use WP\Console\Core\DependencyInjection\ContainerBuilder;
use WP\Console\Bootstrap\AddServicesCompilerPass;
use WP\Console\Bootstrap\FindCommandsCompilerPass;
use WP\Console\Bootstrap\FindGeneratorsCompilerPass;

class WordpressServiceModifier implements ServiceModifierInterface
{
    /**
     * @var string
     */
    protected $root;

    /**
     * @var string
     */
    protected $commandTag;

    /**
     * @var string
     */
    protected $generatorTag;

    /**
     * DrupalServiceModifier constructor.
     * @param string $root
     * @param string $serviceTag
     * @param string $generatorTag
     */
    public function __construct(
        $root = null,
        $serviceTag,
        $generatorTag
    ) {
        $this->root = $root;
        $this->commandTag = $serviceTag;
        $this->generatorTag = $generatorTag;
    }


    /**
     * @inheritdoc
     */
    public function alter(ContainerBuilder $container)
    {
        $container->addCompilerPass(
            new AddServicesCompilerPass($this->root),
            PassConfig::TYPE_BEFORE_OPTIMIZATION
        );
        $container->addCompilerPass(
            new FindCommandsCompilerPass($this->commandTag),
            PassConfig::TYPE_BEFORE_OPTIMIZATION
        );
        $container->addCompilerPass(
            new FindGeneratorsCompilerPass($this->generatorTag),
            PassConfig::TYPE_BEFORE_OPTIMIZATION
        );
    }
}

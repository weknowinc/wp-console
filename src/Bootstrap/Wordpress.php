<?php

namespace WP\Console\Bootstrap;

use WP\Console\Core\Bootstrap\WordpressConsoleCore;
use  WP\Console\Bootstrap\WordpressServiceModifier;

class Wordpress
{
    protected $autoload;
    protected $root;
    protected $appRoot;

    /**
     * Wordpress constructor.
     * @param $autoload
     * @param $appRoot
     */
    public function __construct($autoload, $root, $appRoot)
    {
        $this->autoload = $autoload;
        $this->root = $root;
        $this->appRoot = $appRoot;
    }

    public function boot()
    {
        $wordpress = new WordpressConsoleCore($this->root, $this->appRoot);

        $container = $wordpress->boot();

        $this->addServiceModifier(
            new WordpressServiceModifier(
                $this->root,
                'drupal.command',
                'drupal.generator'
            )
        );

        foreach ($this->serviceModifiers as $serviceModifier) {
            $serviceModifier->alter($container);
        }

        return $wordpress->boot();
    }

    /**
     * @param \WP\Console\Bootstrap\WordpressServiceModifier $serviceModifier
     */
    public function addServiceModifier(WordpressServiceModifier $serviceModifier)
    {
        $this->serviceModifiers[] = $serviceModifier;
    }
}

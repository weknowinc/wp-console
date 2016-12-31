<?php

namespace WP\Console\Bootstrap;

use WP\Console\Core\Bootstrap\WordpressConsoleCore;

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
        return $wordpress->boot();
    }
}

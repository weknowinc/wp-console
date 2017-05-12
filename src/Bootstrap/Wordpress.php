<?php

namespace WP\Console\Bootstrap;

use WP\Console\Core\Bootstrap\WordpressConsoleCore;
use WP\Console\Utils\Site;
use GuzzleHttp\Client;

class Wordpress
{
    protected $autoload;
    protected $root;
    protected $appRoot;

    /**
     * @var Site
     */
    protected $site;

    /**
     * Wordpress constructor.
     *
     * @param $autoload
     * @param $appRoot
     */
    public function __construct($autoload, $root, $appRoot)
    {
        $this->autoload = $autoload;
        $this->root = $root;
        $this->appRoot = $appRoot;
        $this->site = new Site($appRoot, new Client());
    }

    public function boot()
    {
        $wordpress = new WordpressConsoleCore($this->root, $this->appRoot, $this->site);
        $container = $wordpress->boot();

        //$container->compile();


        return $container;
    }
}

<?php

use WP\Console\Application;
use WP\Console\Bootstrap\Wordpress;
use WP\Console\Helper\WordpressFinder;
use WP\Console\Utils\ArgvInputReader;

set_time_limit(0);

if(file_exists(__DIR__ . '/../autoload.local.php')) {
    require_once __DIR__ . '/../autoload.local.php';
}
else {
    $autoloaders = [
        __DIR__ . '/../vendor/autoload.php'
    ];
}

foreach ($autoloaders as $file) {
    if (file_exists($file)) {
        $autoloader = $file;
        break;
    }
}

if (isset($autoloader)) {
    $autoload = require_once $autoloader;
}
else {
    echo ' You must set up the project dependencies using `composer install`' . PHP_EOL;
    exit(1);
}


$wordpressFinder = new WordpressFinder();
$wordpressFinder->locateRoot(getcwd());
$wordpressRoot = $wordpressFinder->getWordpressRoot();
$wordpressConsoleRoot = dirname(__DIR__);

if (!$wordpressRoot) {
    echo ' wp-console must be executed within a Wordpress Site.'.PHP_EOL;
    exit(1);
}

chdir($wordpressRoot);

$wordpress = new Wordpress($autoload, $wordpressConsoleRoot, $wordpressRoot);
$container = $wordpress->boot();

if (!$container) {
    echo ' Something goes wrong. Wordpress can not be bootstrapped.';
    exit(1);
}

$configuration = $container->get('console.configuration_manager')
    ->getConfiguration();

$argvInputReader = new ArgvInputReader();
if ($options = $configuration->get('application.options') ?: []) {
    $argvInputReader->setOptionsFromConfiguration($options);
}
$argvInputReader->setOptionsAsArgv();

$application = new Application($container);
$application->setDefaultCommand('about');
$application->run();

<?php

use WP\Console\Application;
use WP\Console\Bootstrap\Wordpress;
use WP\Console\Core\Utils\ArgvInputReader;
use WP\Console\Helper\WordpressFinder;

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

$argvInputReader = new ArgvInputReader();

// Getting target
$targetConfig = [];
if ($target = $argvInputReader->get('target')) {
    $targetConfig = $container->get('console.configuration_manager')
        ->readTarget($target);
    $argvInputReader->setOptionsFromTargetConfiguration($targetConfig);
}

$argvInputReader->setOptionsAsArgv();

// Getting remote
if ($argvInputReader->get('remote', false)) {
    $commandInput = new ArgvInput();

    /* @var Remote $remote */
    $remote = $container->get('console.remote');
    $commandName = $argvInputReader->get('command', false);

    $remoteSuccess = $remote->executeCommand(
        $io,
        $commandName,
        $target,
        $targetConfig,
        $commandInput->__toString(),
        $configurationManager->getHomeDirectory()
    );

    exit($remoteSuccess?0:1);
}

// Getting wordpress content directory
$wpContentDir = $argvInputReader->get('wp-content-dir');
print "option:" . $wpContentDir . PHP_EOL;
if ($wpContentDir) {
    define('WP_CONTENT_DIR', $wpContentDir );
}

echo "WP Content Directory:" . WP_CONTENT_DIR . PHP_EOL;

$root = $argvInputReader->get('root');
if (!$root) {
    $root = getcwd();
}

$wordpressFinder = new WordpressFinder();
$wordpressFinder->locateRoot($root);
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

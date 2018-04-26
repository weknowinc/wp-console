<?php

namespace WP\Console\Core\Utils;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;
use Dflydev\DotAccessConfiguration\YamlFileConfigurationBuilder;
use Dflydev\DotAccessConfiguration\ConfigurationInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Webmozart\PathUtil\Path;

/**
 * Class ConfigurationManager.
 */
class ConfigurationManager
{
    /**
     * @var ConfigurationInterface
     */
    private $configuration = null;

    /**
     * @var string
     */
    private $applicationDirectory = null;

    /**
     * @var array
     */
    private $missingConfigurationFiles = [];

    /**
     * @var array
     */
    private $configurationDirectories = [];

    /**
     * @param $applicationDirectory
     * @return $this
     */
    public function loadConfiguration($applicationDirectory)
    {
        $this->applicationDirectory = $applicationDirectory . "/";
        $input = new ArgvInput();
        $root = $input->getParameterOption(['--root'], null);

        $configurationDirectories[] = $this->applicationDirectory;
        $configurationDirectories[] = '/etc/wp-console/';
        $configurationDirectories[] = $this->getHomeDirectory() . '/.wp-console/';
        $configurationDirectories[] = getcwd().'/wp-console/';

        if ($root and !in_array($root . 'wp-console/', $configurationDirectories)) {
            $configurationDirectories[] = $root . 'wp-console/';
        }

        $configurationDirectories = array_unique($configurationDirectories);

        $configurationFiles = [];
        foreach ($configurationDirectories as $configurationDirectory) {
            $file = $configurationDirectory . 'config.yml';
            $this->configurationDirectories
            [] = str_replace('//', '/', $configurationDirectory);


            if (!file_exists($file)) {
                $this->missingConfigurationFiles[] = $file;
                continue;
            }
            if (file_get_contents($file)==='') {
                $this->missingConfigurationFiles[] = $file;
                continue;
            }

            $configurationFiles[] = $configurationDirectory . 'config.yml';
        }

        $builder = new YamlFileConfigurationBuilder($configurationFiles);
        $this->configuration = $builder->build();
        $this->appendCommandAliases();

        if ($configurationFiles) {
            $this->missingConfigurationFiles = [];
        }

        return $this;
    }

    public function loadConfigurationFromDirectory($directory)
    {
        $builder = new YamlFileConfigurationBuilder([$directory.'/wp-console/config.yml']);

        return $builder->build();
    }

    /**
     * @return ConfigurationInterface
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    public function readSite($siteFile)
    {
        if (!file_exists($siteFile)) {
            return [];
        }

        return Yaml::parse(file_get_contents($siteFile));
    }

    /**
     * @param $target
     *
     * @return array
     */
    public function readTarget($target)
    {
        if (!$target || !strpos($target, '.')) {
            return [];
        }

        $site = explode('.', $target)[0];
        $env = explode('.', $target)[1];

        $siteFile = sprintf(
            '%s%s%s.yml',
            $this->getSitesDirectory(),
            DIRECTORY_SEPARATOR,
            $site
        );

        if (!file_exists($siteFile)) {
            return [];
        }

        $targetInformation = Yaml::parse(file_get_contents($siteFile));

        if (!array_key_exists($env, $targetInformation)) {
            return [];
        }

        $targetInformation = $targetInformation[$env];

        if (array_key_exists('host', $targetInformation) && $targetInformation['host'] != 'local') {
            $targetInformation['remote'] = true;
        }

        return array_merge(
            $this->configuration->get('application.remote'),
            $targetInformation
        );
    }

    /**
     * Return the user home directory.
     *
     * @return string
     */
    public function getHomeDirectory()
    {
        return Path::getHomeDirectory();
    }

    /**
     * @return string
     */
    public function getApplicationDirectory()
    {
        return $this->applicationDirectory;
    }

    /**
     * Return the site config directory.
     *
     * @return string
     */
    public function getSitesDirectory()
    {
        return null;
        //        return sprintf(
        //            '%s/sites',
        //            $this->getConsoleDirectory()
        //        );
    }

    /**
     * @return string
     */
    public function getConsoleDirectory()
    {
        return sprintf('%s/.wp-console/', $this->getHomeDirectory());
    }

    /**
     * @return array
     */
    public function getMissingConfigurationFiles()
    {
        return $this->missingConfigurationFiles;
    }

    /**
     * @return array
     */
    public function getConfigurationDirectories()
    {
        return $this->configurationDirectories;
    }

    /**
     * @return string
     */
    public function appendCommandAliases()
    {
        $configurationDirectories = array_merge(
            $this->configurationDirectories
        );

        foreach ($configurationDirectories as $directory) {
            $aliasFile = $directory . 'aliases.yml';
            $aliases = [];
            if (file_exists($aliasFile)) {
                $aliases = array_merge(
                    Yaml::parse(file_get_contents($aliasFile)),
                    $aliases
                );
                $this->configuration->set(
                    'application.commands.aliases',
                    $aliases['commands']['aliases']
                );
            }
        }
    }

    public function loadExtendConfiguration()
    {
        $directory = $this->getHomeDirectory() . '/.wp-console/extend/';

        if (!is_dir($directory)) {
            return null;
        }

        $autoloadFile = $directory . 'vendor/autoload.php';
        if (!is_file($autoloadFile)) {
            return null;
        }
        include_once $autoloadFile;
        $extendFile = $directory . 'extend.console.config.yml';

        if (is_file($extendFile) && file_get_contents($extendFile)!='') {
            $builder = new YamlFileConfigurationBuilder([$extendFile]);
            $this->configuration->import($builder->build());
        }
    }

    /**
     * Get the config as array.
     *
     * @return array
     */
    public function getConfigAsArray()
    {
        $filePath = sprintf(
            '%s/.wp-console/config.yml',
            $this->getHomeDirectory()
        );

        $fs = new Filesystem();

        if ($fs->exists($filePath)) {
            $yaml = new Parser();
            $configGlobal = $yaml->parse(file_get_contents($filePath), true);

            return $configGlobal;
        }

        return null;
    }
}

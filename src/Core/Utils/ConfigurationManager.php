<?php

namespace WP\Console\Core\Utils;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Dumper;
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
     * @param $directory
     * @return $this
     */
    public function loadConfiguration($directory)
    {
        $this->locateConfigurationFiles();

        $this->applicationDirectory = $directory;
        if ($directory && is_dir($directory) && strpos($directory, 'phar:')!==0) {
            $this->addConfigurationFilesByDirectory(
                $directory,
                true
            );
        }
        $input = new ArgvInput();
        $root = $input->getParameterOption(['--root']);

        if ($root && is_dir($root)) {
            $this->addConfigurationFilesByDirectory(
                $root. '/wp-console/',
                true
            );
        }

        $builder = new YamlFileConfigurationBuilder(
            $this->configurationFiles['config']
        );

        $this->configuration = $builder->build();

        $extras = [
            'aliases',
            'mappings',
            'defaults'
        ];

        foreach ($extras as $extra) {
            $extraKey = 'application.extras.'.$extra;
            $extraFlag = $this->configuration->get($extraKey)?:'true';
            if ($extraFlag === 'true') {
                $this->appendExtraConfiguration($extra);
            }
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
        $site = $target;
        $environment = null;
        $exploded = explode('.', $target, 2);

        if (count($exploded)>1) {
            $site = $exploded[0];
            $environment = $exploded[1];
        }

        $sites = $this->getSites();
        if (!array_key_exists($site, $sites)) {
            return [];
        }

        $targetInformation = $sites[$site];

        if ($environment) {
            if (!array_key_exists($environment, $sites[$site])) {
                return [];
            }

            $targetInformation = $sites[$site][$environment];
        }

        return $targetInformation;
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
     * @return array
     */
    public function getMissingConfigurationFiles()
    {
        return $this->missingConfigurationFiles;
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

    /**
     * @return void
     */
    private function appendExtraConfiguration($type)
    {
        if (!array_key_exists($type, $this->configurationFiles)) {
            return;
        }

        $configData = [];
        foreach ($this->configurationFiles[$type] as $configFile) {
            if (file_get_contents($configFile)==='') {
                continue;
            }
            $parsed = Yaml::parse(file_get_contents($configFile));
            $configData = array_merge(
                $configData,
                is_array($parsed)?$parsed:[]
            );
        }

        if ($configData && array_key_exists($type, $configData)) {
            $this->configuration->set(
                'application.commands.'.$type,
                $configData[$type]
            );
        }
    }

    private function addConfigurationFilesByDirectory(
        $directory,
        $addDirectory = false
    ) {
        if ($addDirectory) {
            $this->configurationDirectories[] = $directory;
        }
        $configurationFiles = [
            'config' => 'config/config.yml',
            'mappings' => 'config/mappings.yml'
        ];

        foreach ($configurationFiles as $key => $file) {
            $configFile = $directory.$file;
            if (is_file($configFile)) {
                $this->configurationFiles[$key][] = $configFile;
            }
        }
    }

    public function loadExtendConfiguration()
    {
        $directory = $this->getConsoleConfigGlobalDirectory() . 'extend/';
        if (!is_dir($directory)) {
            return null;
        }

        $autoloadFile = $directory . 'vendor/autoload.php';
        if (!is_file($autoloadFile)) {
            return null;
        }
        include_once $autoloadFile;
        $extendFile = $directory . 'extend.console.config.yml';

        $this->importConfigurationFromFile($extendFile);
    }

    /**
     * Get config global as array.
     *
     * @return array
     */
    public function getConfigGlobalAsArray()
    {
        $filePath = sprintf(
            '%s/.wp-console/config.yml',
            $this->getHomeDirectory()
        );

        $fs = new Filesystem();

        if (!$fs->exists($filePath)) {
            return null;
        }

        $yaml = new Parser();
        $configGlobal = $yaml->parse(file_get_contents($filePath), true);

        return $configGlobal;
    }

    /**
     * Update parameter in global config.
     *
     * @param  $configName
     * @param  $value
     * @return int
     */
    public function updateConfigGlobalParameter($configName, $value)
    {
        $parser = new Parser();
        $dumper = new Dumper();

        $userConfigFile = sprintf(
            '%s/.wp-console/config.yml',
            $this->getHomeDirectory()
        );

        if (!file_exists($userConfigFile)) {
            return 1;
        }

        try {
            $userConfigFileParsed = $parser->parse(
                file_get_contents($userConfigFile)
            );
        } catch (\Exception $e) {
        }


        $parents = array_merge(['application'], explode('.', $configName));

        $nestedArray = new NestedArray();

        $nestedArray->setValue(
            $userConfigFileParsed,
            $parents,
            $value,
            true
        );

        try {
            $userConfigFileDump = $dumper->dump($userConfigFileParsed, 10);
        } catch (\Exception $e) {
        }

        try {
            file_put_contents($userConfigFile, $userConfigFileDump);
        } catch (\Exception $e) {
        }
    }

    /**
     * Return the sites config directory.
     *
     * @return array
     */
    private function getSitesDirectories()
    {
        $sitesDirectories = array_map(
            function ($directory) {
                return $directory . 'sites';
            },
            $this->getConfigurationDirectories()
        );

        $sitesDirectories = array_filter(
            $sitesDirectories,
            function ($directory) {
                return is_dir($directory);
            }
        );

        $sitesDirectories = array_unique($sitesDirectories);

        return $sitesDirectories;
    }

    public function getConsoleRoot()
    {
        $consoleCoreDirectory = dirname(dirname(dirname(__FILE__))) . '/';

        if (is_dir($consoleCoreDirectory)) {
            return $consoleCoreDirectory;
        }

        return null;
    }

    public function getConsoleCoreDirectory()
    {
        $consoleCoreDirectory = dirname(dirname(dirname(__FILE__))) . '/src/Core';

        if (is_dir($consoleCoreDirectory)) {
            return $consoleCoreDirectory;
        }

        return null;
    }

    public function getConsoleConfigProjectDirectory()
    {
        return $this->getProjectDirectory().'/wp-console/';
    }

    public function getProjectDirectory()
    {
        return getcwd();
    }

    public function getSystemDirectory()
    {
        $systemDirectory = '/etc/wp-console/';

        if (is_dir($systemDirectory)) {
            return $systemDirectory;
        }

        return null;
    }

    /**
     * @return string
     */
    public function getConsoleConfigGlobalDirectory()
    {
        $consoleDirectory = sprintf(
            '%s/.wp-console/',
            $this->getHomeDirectory()
        );

        if (is_dir($consoleDirectory)) {
            return $consoleDirectory;
        }

        try {
            mkdir($consoleDirectory, 0777, true);
        } catch (\Exception $exception) {
            return null;
        }

        return $consoleDirectory;
    }

    /**
     * @param $includeConsoleCore
     *
     * @return array
     */
    public function getConfigurationDirectories($includeConsoleCore = false)
    {
        if ($this->configurationDirectories) {
            if ($includeConsoleCore) {
                return array_merge(
                    [$this->getConsoleRoot()],
                    $this->configurationDirectories
                );
            }

            return $this->configurationDirectories;
        }

        return [];
    }

    private function locateConfigurationFiles()
    {
        if ($this->getConsoleCoreDirectory()) {
            $this->addConfigurationFilesByDirectory(
                $this->getConsoleCoreDirectory()
            );
        }
        if ($this->getSystemDirectory()) {
            $this->addConfigurationFilesByDirectory(
                $this->getSystemDirectory(),
                true
            );
        }
        if ($this->getConsoleConfigGlobalDirectory()) {
            $this->addConfigurationFilesByDirectory(
                $this->getConsoleConfigGlobalDirectory(),
                true
            );
        }
        if ($this->getConsoleConfigProjectDirectory()) {
            $this->addConfigurationFilesByDirectory(
                $this->getConsoleConfigProjectDirectory(),
                true
            );
        }
    }


    private function importConfigurationFromFile($configFile)
    {
        if (is_file($configFile) && file_get_contents($configFile)!='') {
            $builder = new YamlFileConfigurationBuilder([$configFile]);
            if ($this->configuration) {
                $this->configuration->import($builder->build());
            } else {
                $this->configuration = $builder->build();
            }
        }
    }

    /**
     * @return array
     */
    public function getSites()
    {
        if ($this->sites) {
            return $this->sites;
        }

        $sitesDirectories = $this->getSitesDirectories();

        if (!$sitesDirectories) {
            return [];
        }

        $finder = new Finder();
        $finder->in($sitesDirectories);
        $finder->name("*.yml");

        foreach ($finder as $site) {
            $siteName = $site->getBasename('.yml');
            $environments = $this->readSite($site->getRealPath());

            if (!$environments || !is_array($environments)) {
                continue;
            }

            $this->sites[$siteName] = [
                'file' => $site->getRealPath()
            ];

            foreach ($environments as $environment => $config) {
                if (!array_key_exists('type', $config)) {
                    throw new \UnexpectedValueException(
                        sprintf(
                            "The 'type' parameter is required in sites configuration:\n %s.", $site->getPathname()
                        )
                    );
                }
                if ($config['type'] !== 'local') {
                    if (array_key_exists('host', $config)) {
                        $targetInformation['remote'] = true;
                    }

                    $config = array_merge(
                        $this->configuration->get('application.remote')?:[],
                        $config
                    );
                }

                $this->sites[$siteName][$environment] = $config;
            }
        }

        return $this->sites;
    }

    /**
     * @return array
     */
    public function getConfigurationFiles()
    {
        return $this->configurationFiles;
    }
}

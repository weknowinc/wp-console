<?php

namespace WP\Console\Utils;

use GuzzleHttp\Client;

class Site
{
    protected $appRoot;

    /**
     * @var Client
     */

    protected $httpClient;

    /**
     * Site constructor.
     *
     * @param $appRoot
     * @param Client $httpClient
     */
    public function __construct($appRoot, Client $httpClient)
    {
        $this->appRoot = $appRoot;
        $this->httpClient = $httpClient;
    }

    public function loadLegacyFile($legacyFile, $relative = true)
    {
        if ($relative) {
            $legacyFile = realpath(
                sprintf('%s/%s', $this->appRoot, $legacyFile)
            );
        }

        if (file_exists($legacyFile)) {
            require_once $legacyFile;

            return true;
        }

        return false;
    }

    public function getConfig() {

        if (!empty($this->appRoot) && is_dir($this->appRoot) && file_exists($this->appRoot . '/wp-config.php')) {
            return $this->appRoot . '/wp-config.php';
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function isInstalled() {

        if(function_exists('is_blog_installed')) {
            if (is_blog_installed()) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function getLanguages() {

        $languages['en'] = 'English (United States)';

        $availableTranslationsResponse = $this->httpClient->request('GET', 'http://api.wordpress.org/translations/core/1.0/');

        if ($availableTranslationsResponse->getStatusCode() != 200) {
            throw new \Exception('Invalid path.');
        }

        try {
            $availableTranslations = json_decode(
                $availableTranslationsResponse->getBody()->getContents()
            );
        } catch (\Exception $e) {
            return $languages;
        }

        foreach($availableTranslations->translations as $translation) {
            $languages[$translation->language] = $translation->native_name;
        }

        return $languages;
    }

    /**
     * @return array
     */
    /*public function getStandardLanguages()
    {
        $standardLanguages = LanguageManager::getStandardLanguageList();
        $languages = [];
        foreach ($standardLanguages as $langcode => $standardLanguage) {
            $languages[$langcode] = $standardLanguage[0];
        }

        return $languages;
    }*/

    /**
     * @return array
     */
    /*public function getDatabaseTypes()
    {
        $this->loadLegacyFile('/core/includes/install.inc');
        $this->setMinimalContainerPreKernel();

        $finder = new Finder();
        $finder->directories()
            ->in($this->appRoot . '/core/lib/Drupal/Core/Database/Driver')
            ->depth('== 0');

        $databases = [];
        foreach ($finder as $driver_folder) {
            if (file_exists($driver_folder->getRealpath() . '/Install/Tasks.php')) {
                $driver  = $driver_folder->getBasename();
                $installer = db_installer_object($driver);
                // Verify is database is installable
                if ($installer->installable()) {
                    $reflection = new \ReflectionClass($installer);
                    $install_namespace = $reflection->getNamespaceName();
                    // Cut the trailing \Install from namespace.
                    $driver_class = substr($install_namespace, 0, strrpos($install_namespace, '\\'));
                    $databases[$driver] = ['namespace' => $driver_class, 'name' =>$installer->name()];
                }
            }
        }

        return $databases;
    }*/

    /*protected function setMinimalContainerPreKernel()
    {
        // Create a minimal mocked container to support calls to t() in the pre-kernel
        // base system verification code paths below. The strings are not actually
        // used or output for these calls.
        $container = new ContainerBuilder();
        $container->setParameter('language.default_values', Language::$defaultValues);
        $container
            ->register('language.default', 'Drupal\Core\Language\LanguageDefault')
            ->addArgument('%language.default_values%');
        $container
            ->register('string_translation', 'Drupal\Core\StringTranslation\TranslationManager')
            ->addArgument(new Reference('language.default'));

        // Register the stream wrapper manager.
        $container
            ->register('stream_wrapper_manager', 'Drupal\Core\StreamWrapper\StreamWrapperManager')
            ->addMethodCall('setContainer', [new Reference('service_container')]);
        $container
            ->register('file_system', 'Drupal\Core\File\FileSystem')
            ->addArgument(new Reference('stream_wrapper_manager'))
            ->addArgument(Settings::getInstance())
            ->addArgument((new LoggerChannelFactory())->get('file'));

        \Drupal::setContainer($container);
    }*/

    /*public function getDatabaseTypeDriver($driver)
    {
        // We cannot use Database::getConnection->getDriverClass() here, because
        // the connection object is not yet functional.
        $task_class = "Drupal\\Core\\Database\\Driver\\{$driver}\\Install\\Tasks";
        if (class_exists($task_class)) {
            return new $task_class();
        } else {
            $task_class = "Drupal\\Driver\\Database\\{$driver}\\Install\\Tasks";
            return new $task_class();
        }
    }*/

    /**
     * @return mixed
     */
    /*public function getAutoload()
    {
        $autoLoadFile = $this->appRoot.'/autoload.php';

        return include $autoLoadFile;
    }*/

    /**
     * @return boolean
     */
    public function multisiteMode($uri)
    {
        if ($uri != 'default') {
            return true;
        }

        return false;
    }

    /**
     * @return boolean
     */
    /*public function validMultisite($uri)
    {
        $multiSiteFile = sprintf(
            '%s/sites/sites.php',
            $this->appRoot
        );

        if (file_exists($multiSiteFile)) {
            include $multiSiteFile;
        } else {
            return false;
        }

        if (isset($sites[$uri]) && is_dir($this->appRoot . "/sites/" . $sites[$uri])) {
            return true;
        }

        return false;
    }*/
}

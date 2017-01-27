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

    /**
     * @return bool
     */
    public function isMultisite() {

        if(function_exists('is_multisite')) {
            if (is_multisite()) {
                return true;
            } else {
                return false;
            }
        } else {
            print 'not found';
            return false;
        }
    }

    /**
     * @return WP_User|false WP_User object on success, false on failure.
     */
    public function getUserByField($field, $value) {

        if(function_exists('get_user_by')) {
            return get_user_by($field,$value);
        } else {
            return false;
        }
    }

    /**
     * @return WP_User|false WP_User object on success, false on failure.
     */
    public function getUsers($fields) {

        if(function_exists('get_users')) {
            return get_users($fields);
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function isSuperAdmin($userId) {

        if(function_exists('is_super_admin')) {
            return is_super_admin($userId);
        } else {
            return false;
        }
    }

    public function addSiteAdmin( $newAdmin ) {
        $site_admins = array( $newAdmin->user_login );
        $users = $this->getUsers(array( 'fields' => array( 'ID', 'user_login' )));
        if ( $users ) {
            foreach ( $users as $user ) {
                if ( $this->isSuperAdmin($user->ID) && !in_array( $user->user_login, $site_admins ) )
                    $site_admins[] = $user->user_login;
            }
        }

        $this->updateOption('site_admins', $site_admins );
    }

    /**
     * @return bool
     */
    public function updateUserMeta($userId, $key, $value, $prevValue = '') {
        if(function_exists('update_user_meta')) {
            return update_user_meta($userId, $key, $value, $prevValue);
        } else {
            return null;
        }
    }

    public function createNetwork( $networkId, $blogId, $domain, $path, $subdomains, $user ) {
        global $wpdb, $current_site, $wp_rewrite;

        $current_site = new \stdClass;
        $current_site->domain = $domain;
        $current_site->path = $path;
        $current_site->site_name = ucfirst( $domain );

        $wpdb->insert( $wpdb->blogs, array(
            'site_id' => $networkId,
            'domain' => $domain,
            'path' => $path,
            'registered' => current_time( 'mysql' )
        ) );

        $current_site->blog_id = $blogId = $wpdb->insert_id;
        $this->updateUserMeta($user->ID, 'source_domain', $domain );
        $this->updateUserMeta($user->ID, 'primary_blog', $blogId );

        if ($subdomains)
            $wp_rewrite->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );
        else
            $wp_rewrite->set_permalink_structure( '/blog/%year%/%monthnum%/%day%/%postname%/' );

        $this->flushRewriteRules();
    }

    /**
     * @return bool
     */
    public function flushRewriteRules() {
        if(function_exists('flush_rewrite_rules')) {
            return flush_rewrite_rules();
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    public function getTitle() {

        if(function_exists('get_bloginfo')) {
           return get_bloginfo();
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    public function getEmail() {

        if(function_exists('get_option')) {
            return get_option( 'admin_email' );
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    public function getDomain() {
        $domain = preg_replace('|https?://|', '', $this->getsiteUrl());
        if ( $slash = strpos( $domain, '/' ) )
            $domain = substr( $domain, 0, $slash );
        return $domain;
    }

    /**
     * @return string
     */
    public function getsiteUrl() {
        if(function_exists('get_option')) {
            return get_option('siteurl');
        } else {
            return null;
        }
    }

    /**
     * @return bool
     */
    public function deleteOption($option) {
        if(function_exists('delete_site_option')) {
            return delete_site_option($option);
        } else {
            return null;
        }
    }

    /**
     * @return bool
     */
    public function updateOption($option, $value) {
        if(function_exists('update_site_option')) {
            return update_site_option($option, $value);
        } else {
            return null;
        }
    }

    /**
     * @return mixed
     */
    public function getTables($scope = 'all') {
        global $wpdb;
        if(is_object($wpdb)) {
            return $wpdb->tables($scope);
        } else {
            return [];
        }
    }

    public function setSiteURL($scheme, $uri) {
        $_SERVER['SERVER_NAME'] = $uri;

        if(!defined( 'WP_SITEURL' ) ) {
            define('WP_SITEURL', $scheme . '://' . $uri);
        }
    }

    // Setup global $_SERVER variables to keep WP from trying to redirect
    public function setGlobalServer($uri,$basePath = '/', $method = 'GET') {
        $_SERVER['HTTP_HOST'] = $uri;
        $_SERVER['SERVER_NAME'] = $uri;
        $_SERVER['REQUEST_URI'] = $basePath;
        $_SERVER['REQUEST_METHOD'] = $method;
    }

    public function extractConstants($filename){
        $constants = [];
        if (file_exists($filename)) {
            //$content=fopen($filename,'r');
            $lines = file($filename);
            foreach ($lines as $line) {
                preg_match_all("/^define\((.*)\);$/m", $line, $m);
                if (!empty($m[1])) {
                    $line = $m[1][0];
                } else {
                    continue;
                }
                list($key, $value) = explode(',', $line);
                $key = ltrim(trim(str_replace('"', '', str_replace("'", '', $key))));
                $value = ltrim(trim(str_replace('"', '', str_replace("'", '', $value))));
                $constants[$key] = $value;
            }
        }

        return $constants;
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

    public function setCurrentUser($userID){
        if(function_exists('wp_set_current_user')) {
            return wp_set_current_user($userID);
        } else {
            return null;
        }
    }

    public function getUserSites($userID){
        if(function_exists('get_blogs_of_user')) {
            return get_blogs_of_user($userID);
        } else {
            return null;
        }
    }

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

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
     * @param Client  $httpClient
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
            include_once $legacyFile;

            return true;
        }

        return false;
    }

    public function getConfig()
    {
        if (!empty($this->appRoot) && is_dir($this->appRoot) && file_exists($this->appRoot . '/wp-config.php')) {
            return $this->appRoot . '/wp-config.php';
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function isInstalled()
    {
        if (function_exists('is_blog_installed')) {
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
    public function isMultisite()
    {
        if (function_exists('is_multisite')) {
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
    public function getUserByField($field, $value)
    {
        if (function_exists('get_user_by')) {
            return get_user_by($field, $value);
        } else {
            return false;
        }
    }

    /**
     * @return WP_User|false WP_User object on success, false on failure.
     */
    public function getUsers($fields)
    {
        if (function_exists('get_users')) {
            return get_users($fields);
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function isSuperAdmin($userId)
    {
        if (function_exists('is_super_admin')) {
            return is_super_admin($userId);
        } else {
            return false;
        }
    }

    public function addSiteAdmin($newAdmin)
    {
        $site_admins = array( $newAdmin->user_login );
        $users = $this->getUsers(array( 'fields' => array( 'ID', 'user_login' )));
        if ($users) {
            foreach ($users as $user) {
                if ($this->isSuperAdmin($user->ID) && !in_array($user->user_login, $site_admins)) {
                    $site_admins[] = $user->user_login;
                }
            }
        }

        $this->updateOption('site_admins', $site_admins);
    }

    /**
     * @return bool
     */
    public function updateUserMeta($userId, $key, $value, $prevValue = '')
    {
        if (function_exists('update_user_meta')) {
            return update_user_meta($userId, $key, $value, $prevValue);
        } else {
            return null;
        }
    }


    /**
     * @return int
     */
    public function getCurrentNetworkID()
    {
        if (function_exists('get_current_network_id')) {
            return get_current_network_id();
        } else {
            return null;
        }
    }


    /**
     * @param $domain
     * @param $path
     * @param $title
     * @param $user_id
     * @param $meta
     * @param $site_id
     * @return int|WP_Error
     */
    public function createMultisiteBlog($domain, $path, $title, $user_id, $meta, $site_id)
    {
        if (function_exists('wpmu_create_blog')) {
            return wpmu_create_blog($domain, $path, $title, $user_id, $meta, $site_id);
        } else {
            return null;
        }
    }
    public function createNetwork($networkId, $blogId, $domain, $path, $subdomains, $user)
    {
        global $wpdb, $current_site, $wp_rewrite;

        $current_site = new \stdClass;
        $current_site->domain = $domain;
        $current_site->path = $path;
        $current_site->site_name = ucfirst($domain);

        $wpdb->insert(
            $wpdb->blogs, array(
                'site_id' => $networkId,
                'domain' => $domain,
                'path' => $path,
                'registered' => current_time('mysql')
            )
        );

        $current_site->blog_id = $blogId = $wpdb->insert_id;
        $this->updateUserMeta($user->ID, 'source_domain', $domain);
        $this->updateUserMeta($user->ID, 'primary_blog', $blogId);

        if ($subdomains) {
            $wp_rewrite->set_permalink_structure('/%year%/%monthnum%/%day%/%postname%/');
        } else {
            $wp_rewrite->set_permalink_structure('/blog/%year%/%monthnum%/%day%/%postname%/');
        }

        $this->flushRewriteRules();
    }

    /**
     * @return bool
     */
    public function flushRewriteRules()
    {
        if (function_exists('flush_rewrite_rules')) {
            return flush_rewrite_rules();
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->getBlogInfo();
    }

    /**
     * @return string
     */
    public function getBlogInfo($info = '', $filter = 'raw')
    {
        if (function_exists('get_bloginfo')) {
            return get_bloginfo($info, $filter);
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        if (function_exists('get_option')) {
            return get_option('admin_email');
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        $domain = preg_replace('|https?://|', '', $this->getSiteUrl());
        if ($slash = strpos($domain, '/')) {
            $domain = substr($domain, 0, $slash);
        }
        return $domain;
    }

    /**
     * @return object | null
     */
    public function getCurrentSite()
    {
        if (function_exists('get_current_site')) {
            return get_current_site();
        } else {
            return null;
        }
    }

    /**
     * @return boolean
     */
    public function canInstallLanguagePack()
    {
        if (function_exists('wp_can_install_language_pack')) {
            return wp_can_install_language_pack();
        } else {
            return null;
        }
    }

    /**
     * @return mixed
     */
    public function unslash($value)
    {
        if (function_exists('wp_unslash')) {
            return wp_unslash($value);
        } else {
            return null;
        }
    }

    /**
     * @param string $plugin_folder
     * @return null
     */
    public function getPlugins($plugin_folder = '')
    {
        if (function_exists('get_plugins')) {
            return get_plugins($plugin_folder);
        } else {
            return null;
        }
    }



    /**
     * @return mixed
     */
    public function emailExists($email)
    {
        if (function_exists('email_exists')) {
            return email_exists($email);
        } else {
            return null;
        }
    }

    /**
     * @return mixed
     */
    public function doAction($action, $argument)
    {
        if (function_exists('do_action')) {
            return do_action($action, $argument);
        } else {
            return null;
        }
    }

    /**
     * @return boolean
     */
    public function usernameExists($username)
    {
        if (function_exists('username_exists')) {
            return username_exists($username);
        } else {
            return null;
        }
    }

    /**
     * @return mixed
     */
    public function downloadLanguagePack($langcode)
    {
        if (function_exists('wp_download_language_pack')) {
            return wp_download_language_pack($langcode);
        } else {
            return null;
        }
    }

    /**
     * @param $length
     * @param $special_chars
     * @param $extra_special_chars
     * @return string
     */
    public function generatePassword($length = 12, $special_chars = true, $extra_special_chars = false)
    {
        if (function_exists('wp_generate_password')) {
            return wp_generate_password($length, $special_chars, $extra_special_chars);
        } else {
            return null;
        }
    }

    /**
     * @param $userName
     * @param $password
     * @param $email
     * @return int|false
     */
    public function createMultisiteUser($userName, $password, $email)
    {
        if (function_exists('wpmu_create_user')) {
            return wpmu_create_user($userName, $password, $email);
        } else {
            return null;
        }
    }

    public function mail($to, $subject, $message, $headers = '', $attachments = array())
    {
        if (function_exists('wp_mail')) {
            return wp_mail($to, $subject, $message, $headers, $attachments);
        } else {
            return null;
        }
    }

    public function multisiteWelcomeNotification($blog_id, $user_id, $password, $title, $meta = array())
    {
        if (function_exists('wpmu_welcome_notification')) {
            return wpmu_welcome_notification($blog_id, $user_id, $password, $title, $meta);
        } else {
            return null;
        }
    }


    /**
     * @return string
     */
    public function getSiteUrl($blog_id = null, $path = '', $scheme = null)
    {
        if (function_exists('get_site_url')) {
            return get_site_url($blog_id, $path, $scheme);
        } else {
            return null;
        }
    }

    /**
     * @return bool
     */
    public function deleteOption($option)
    {
        if (function_exists('delete_site_option')) {
            return delete_site_option($option);
        } else {
            return null;
        }
    }

    /**
     * @return bool
     */
    public function updateOption($option, $value)
    {
        if (function_exists('update_site_option')) {
            return update_site_option($option, $value);
        } else {
            return null;
        }
    }

    /**
     * @param $option
     * @param $userID
     * @return boolean
     */
    public function updateUserOption($userID, $option_name, $newvalue, $global)
    {
        if (function_exists('update_user_option')) {
            return update_user_option($userID, $option_name, $newvalue, $global);
        } else {
            return null;
        }
    }


    /**
     * @return mixed
     */
    public function getTables($scope = 'all')
    {
        global $wpdb;
        if (is_object($wpdb)) {
            return $wpdb->tables($scope);
        } else {
            return [];
        }
    }


    public function setSiteURL($scheme, $uri)
    {
        $_SERVER['SERVER_NAME'] = $uri;

        if (!defined('WP_SITEURL')) {
            define('WP_SITEURL', $scheme . '://' . $uri);
        }
    }

    // Setup global $_SERVER variables to keep WP from trying to redirect
    public function setGlobalServer($uri, $basePath = '/', $method = 'GET')
    {
        $_SERVER['HTTP_HOST'] = $uri;
        $_SERVER['SERVER_NAME'] = $uri;
        $_SERVER['REQUEST_URI'] = $basePath;
        $_SERVER['REQUEST_METHOD'] = $method;
    }

    public function extractConstants($filename)
    {
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

    public function getLanguages()
    {
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

        foreach ($availableTranslations->translations as $translation) {
            $languages[$translation->language] = $translation->native_name;
        }

        return $languages;
    }

    public function setCurrentUser($userID)
    {
        if (function_exists('wp_set_current_user')) {
            return wp_set_current_user($userID);
        } else {
            return null;
        }
    }

    public function getCurrentUser()
    {
        if (function_exists('wp_get_current_user')) {
            return wp_get_current_user();
        } else {
            return null;
        }
    }

    public function isPluginActive($plugin)
    {
        if (function_exists('is_plugin_active')) {
            return is_plugin_active($plugin);
        } else {
            return null;
        }
    }

    public function activatePlugin($plugin, $redirect = '', $network_wide = false, $silent = false)
    {
        if (function_exists('activate_plugin')) {
            return activate_plugin($plugin, $redirect, $network_wide, $silent);
        } else {
            return null;
        }
    }

    public function cacheFlush()
    {
        if (function_exists('wp_cache_flush')) {
            return wp_cache_flush();
        } else {
            return null;
        }
    }

    /**
     * @param $plugins
     * @param bool    $silent
     * @param null    $network_wide
     * @return null
     */
    public function deactivatePlugins($plugins, $silent = false, $network_wide = null)
    {
        if (function_exists('deactivate_plugins')) {
            return deactivate_plugins($plugins, $silent, $network_wide);
        } else {
            return null;
        }
    }



    /**
     * @param $userID
     * @return mixed
     */
    public function getUserSites($userID)
    {
        if (function_exists('get_blogs_of_user')) {
            return get_blogs_of_user($userID);
        } else {
            return null;
        }
    }

    /**
     * @param $option
     * @param $default
     * @return mixed
     */
    public function getOption($option, $default = false)
    {
        if (function_exists('get_site_option')) {
            return get_site_option($option, $default);
        } else {
            return null;
        }
    }

    /**
     * @param $option
     * @param $userID
     * @return mixed
     */
    public function getUserOption($option, $userID)
    {
        if (function_exists('get_user_option')) {
            return get_user_option($option, $userID);
        } else {
            return null;
        }
    }

    /**
     * @return bool
     */
    public function isMulsiteSubdomain()
    {
        if (function_exists('is_subdomain_install')) {
            return is_subdomain_install();
        } else {
            return null;
        }
    }

    /**
     * @return bool
     */
    public function getSubdirectoryReservedNames()
    {
        if (function_exists('get_subdirectory_reserved_names')) {
            return get_subdirectory_reserved_names();
        } else {
            return null;
        }
    }

    /**
     * @param string $theme_folder
     * @return null
     */
    public function getThemes($theme_folder = '')
    {
        if (function_exists('wp_get_themes')) {
            $themes = array_keys(wp_get_themes($theme_folder));
            $theme_data = [];
            foreach ($themes as $data) {
                $theme =  wp_get_theme($data);
                $theme_data [$data] = array(
                    'Name' => $theme->get('Name'),
                    'URI' => $theme->display('ThemeURI', true, false),
                    'Description' => $theme->display('Description', true, false),
                    'Author' => $theme->display('Author', true, false),
                    'AuthorURI' => $theme->display('AuthorURI', true, false),
                    'Version' => $theme->get('Version'),
                    'Template' => $theme->get('Template'),
                    'Status' => $theme->get('Status'),
                    'Tags' => $theme->get('Tags'),
                    'Title' => $theme->get('Name'),
                    'AuthorName' => $theme->get('Author'),
                );
            }
            return  $theme_data;
        } else {
            return null;
        }
    }

    public function activateTheme($theme)
    {
        if (function_exists('switch_theme')) {
            return switch_theme($theme);
        } else {
            return null;
        }
    }

    public function isThemeActive($theme)
    {
        if (function_exists('wp_get_theme')) {
            if (wp_get_theme()->stylesheet === $theme) {
                return true;
            } else {
                return false;
            }
        } else {
            return null;
        }
    }

    public function getUserBy($field, $value)
    {
        $this->loadLegacyFile('wp-includes/pluggable.php');

        if (function_exists('get_user_by')) {
            return get_user_by($field, $value);
        } else {
            return null;
        }
    }

    public function insertUser($userdata)
    {
        $this->loadLegacyFile('wp-includes/user.php');

        if (function_exists('wp_insert_user')) {
            return wp_insert_user($userdata);
        } else {
            return null;
        }
    }

    public function getShortCodes()
    {
        global $shortcode_tags;

        return $shortcode_tags;
    }
}

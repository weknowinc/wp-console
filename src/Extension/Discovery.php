<?php

/**
 * @file
 * Contains \WP\Console\Extension\Discovery.
 */

namespace WP\Console\Extension;

use WP\Console\Component\FileCache\FileCacheFactory;
use WP\Console\Utils\Site;


class Discovery
{

    /**
     * @var array
     */
    protected static $files = array();

    /**
     * @var Site
     */
    protected $site;


    /**
     * The app root for the current operation.
     *
     * @var string
     */
    protected $root;


    /**
     * The file cache object.
     *
     * @var \WP\Console\Component\FileCache\FileCacheInterface
     */
    protected $fileCache;

    /**
     * The site path.
     *
     * @var string
     */
    protected $sitePath;

    /**
     * Constructs a new ExtensionDiscovery object.
     *
     * @param Site $site
     * @param string $root
     *   The app root.
     * @param bool $use_file_cache
     *   Whether file cache should be used.
     * @param string $site_path
     *   The path to the site.
     */
    public function __construct(Site $site, $root, $use_file_cache = TRUE, $site_path = NULL) {
        $this->site = $site;
        $this->root = $root;
        $this->fileCache = $use_file_cache ? FileCacheFactory::get('extension_discovery') : NULL;
        $this->sitePath = $site_path;
    }

    /**
     * Reset internal static cache.
     */
    public static function reset()
    {
        static::$files = [];
    }

    /**
     * @param string $type
     *   The extension type to search for. One of 'plugin', 'Theme'.
     * @return mixed An associative array of Extension objects, keyed by extension name
     */
    public function scan($type) {
        if($type == 'plugin') {
            if($plugins = $this->fileCache->get($type)) {
                return $plugins;
            } else {
                /** WordPress Plugin Administration API */
                $this->site->loadLegacyFile('wp-admin/includes/plugin.php');
                $plugins = $this->site->getPlugins();
                $this->fileCache->set($type, $plugins);
                return $plugins;
            }
        } elseif($type == 'theme') {
            if($themes = $this->fileCache->get($type)) {
                return $themes;
            } else {
                /** WordPress Theme Administration API */
                $this->site->loadLegacyFile('wp-includes/theme.php');
                $themes = $this->site->getThemes();
                $this->fileCache->set($type, $themes);
                return $themes;
            }
        }
    }

}

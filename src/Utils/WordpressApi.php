<?php

/**
 * @file
 * Contains WP\Console\Utils\WordpressApi.
 */

namespace WP\Console\Utils;

use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;

/**
 * Class WPHelper
 *
 * @package WP\Console\Utils
 */
class WordpressApi
{
    protected $appRoot;
    protected $site;

    private $roles = [];

    /**
     * DebugCommand constructor.
     *
     * @param Client  $httpClient
     */

    protected $httpClient;

    /**
     * ServerCommand constructor.
     *
     * @param $appRoot
     * @param $entityTypeManager
     */
    public function __construct($appRoot, Site $site, Client $httpClient)
    {
        $this->appRoot = $appRoot;
        $this->site = $site;
        $this->httpClient = $httpClient;
    }

    /**
     * @return string
     */
    public function getWPVersion()
    {
        $this->site->loadLegacyFile('wp-includes/general-template.php');
        return get_bloginfo('version');
    }

    /**
     * @param bool|FALSE $reset
     *
     * @return array
     */
    public function getRoles($reset=false)
    {
        if ($reset || !$this->roles) {
            $this->site->loadLegacyFile('wp-admin/includes/user.php');

            $roles = get_editable_roles();

            foreach ($roles as $key => $role) {
                $this->roles[$key] = $role['name'];
            }
        }

        return $this->roles;
    }

    /**
     * @return array
     */
    public function getCapabilities($role = null)
    {
        if (is_null($role)) {
            $capabilities = ['create_sites', 'delete_sites', 'manage_network', 'manage_sites', 'manage_network_users',
                'manage_network_plugins', 'manage_network_themes', 'manage_network_options', 'upgrade_network', 'setup_network',
                'activate_plugins', 'delete_others_pages', 'delete_others_posts', 'delete_pages', 'delete_posts',
                'delete_private_pages', 'delete_private_posts', 'delete_published_pages', 'delete_published_posts',
                'edit_dashboard', 'edit_others_pages', 'edit_others_posts', 'edit_pages', 'edit_posts', 'edit_private_pages',
                'edit_private_posts', 'edit_published_pages', 'edit_published_posts', 'edit_theme_options', 'export', 'import',
                'list_users', 'manage_categories', 'manage_links', 'manage_options', 'moderate_comments', 'promote_users',
                'publish_pages', 'publish_posts', 'read_private_pages', 'read_private_posts', 'read', 'remove_users',
                'switch_themes', 'upload_files', 'customize', 'delete_site', 'update_core', 'update_plugins', 'update_themes',
                'install_plugins', 'install_themes', 'upload_plugins', 'upload_themes', 'delete_themes', 'delete_plugins',
                'edit_plugins', 'edit_themes', 'edit_files', 'edit_users', 'create_users', 'delete_users', 'unfiltered_html',
                'unfiltered_upload', 'add_users', 'edit_comment', 'approve_comment', 'unapprove_comment', 'reply_comment',
                'quick_edit_comment', 'spam_comment', 'unspam_comment', 'trash_comment', 'untrash_comment', 'delete_comment',
                'edit_permalink', 'level_10', 'level_9', 'level_8', 'level_7', 'level_6', 'level_5', 'level_4', 'level_3',
                'level_2', 'level_1', 'level_0'
            ];
        } else {
            $this->site->loadLegacyFile('wp-includes/capabilities.php');
            $capabilities = get_role($role);
            $capabilities = $capabilities->capabilities;
        }

        return $capabilities;
    }

    /**
     * @param $module
     * @param $limit
     * @param $stable
     * @return array
     * @throws \Exception
     */
    public function getProjectReleases($module, $limit = 10, $stable = false)
    {
        if (!$module) {
            return [];
        }

        $projectPageResponse = $this->httpClient->getUrlAsString(
            sprintf(
                'https://updates.WP.org/release-history/%s/8.x',
                $module
            )
        );

        if ($projectPageResponse->getStatusCode() != 200) {
            throw new \Exception('Invalid path.');
        }

        $releases = [];
        $crawler = new Crawler($projectPageResponse->getBody()->getContents());
        $filter = './project/releases/release/version';
        if ($stable) {
            $filter = './project/releases/release[not(version_extra)]/version';
        }

        foreach ($crawler->filterXPath($filter) as $element) {
            $releases[] = $element->nodeValue;
        }

        if (count($releases)>$limit) {
            array_splice($releases, $limit);
        }

        return $releases;
    }

    /**
     * @param $project
     * @param $release
     * @param null    $destination
     * @return null|string
     */
    public function downloadProjectRelease($project, $release, $destination = null)
    {
        if (!$release) {
            $releases = $this->getProjectReleases($project, 1);
            $release = current($releases);
        }

        if (!$destination) {
            $destination = sprintf(
                '%s/%s.tar.gz',
                sys_get_temp_dir(),
                $project
            );
        }

        $releaseFilePath = sprintf(
            'https://ftp.WP.org/files/projects/%s-%s.tar.gz',
            $project,
            $release
        );

        if ($this->downloadFile($releaseFilePath, $destination)) {
            return $destination;
        }

        return null;
    }

    public function downloadFile($url, $destination)
    {
        $this->httpClient->get($url, ['sink' => $destination]);

        return file_exists($destination);
    }



    /**
     * Gets WP releases from Packagist API.
     *
     * @param string $url
     * @param int    $limit
     * @param bool   $unstable
     *
     * @return array
     */
    private function getComposerReleases($url, $limit = 10, $unstable = false)
    {
        if (!$url) {
            return [];
        }

        $packagistResponse = $this->httpClient->getUrlAsString($url);

        if ($packagistResponse->getStatusCode() != 200) {
            throw new \Exception('Invalid path.');
        }

        try {
            $packagistJson = json_decode(
                $packagistResponse->getBody()->getContents()
            );
        } catch (\Exception $e) {
            return [];
        }

        $versions = array_keys((array)$packagistJson->package->versions);

        // Remove WP 7 versions
        $i = 0;
        foreach ($versions as $version) {
            if (0 === strpos($version, "7.") || 0 === strpos($version, "dev-7.")) {
                unset($versions[$i]);
            }
            $i++;
        }

        if (!$unstable) {
            foreach ($versions as $key => $version) {
                if (strpos($version, "-")) {
                    unset($versions[$key]);
                }
            }
        }

        if (is_array($versions)) {
            return array_slice($versions, 0, $limit);
        }

        return [];
    }

    /**
     * @Todo: Remove when issue https://www.WP.org/node/2556025 get resolved
     *
     * Rebuilds all caches even when WP itself does not work.
     *
     * @param \Composer\Autoload\ClassLoader            $class_loader
     *   The class loader.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The current request.
     *
     * @see rebuild.php
     */
    public function WP_rebuild($class_loader, \Symfony\Component\HttpFoundation\Request $request)
    {
        // Remove WP's error and exception handlers; they rely on a working
        // service container and other subsystems and will only cause a fatal error
        // that hides the actual error.
        restore_error_handler();
        restore_exception_handler();

        // Force kernel to rebuild php cache.
        \WP\Core\PhpStorage\PhpStorageFactory::get('twig')->deleteAll();

        // Bootstrap up to where caches exist and clear them.
        $kernel = new \Drupal\Core\DrupalKernel('prod', $class_loader);
        $kernel->setSitePath(\Drupal\Core\DrupalKernel::findSitePath($request));

        // Invalidate the container.
        $kernel->invalidateContainer();

        // Prepare a NULL request.
        $kernel->prepareLegacyRequest($request);

        foreach (Cache::getBins() as $bin) {
            $bin->deleteAll();
        }

        // Disable recording of cached pages.
        \Drupal::service('page_cache_kill_switch')->trigger();

        drupal_flush_all_caches();

        // Restore Drupal's error and exception handlers.
        // @see \Drupal\Core\DrupalKernel::boot()
        set_error_handler('_drupal_error_handler');
        set_exception_handler('_drupal_exception_handler');
    }
}

<?php

namespace WP\Console\Extension;

use WP\Console\Utils\Site;

/**
 * Class ExtensionManager
 *
 * @package WP\Console
 */
class Manager
{
    /**
     * @var Site
     */
    protected $site;
    /**
     * @var string
     */
    protected $appRoot;

    /**
     * @var array
     */
    private $extensions = [];

    /**
     * @var array
     */
    private $filters = [];

    /**
     * @var string
     */
    private $extension = null;

    /**
     * ExtensionManager constructor.
     *
     * @param Site   $site
     * @param string $appRoot
     */
    public function __construct(
        Site $site,
        $appRoot
    ) {
        $this->site = $site;
        $this->appRoot = $appRoot;
        $this->initialize();
    }

    /**
     * @return $this
     */
    public function showActivated()
    {
        $this->filters['showActivated'] = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function showDeactivated()
    {
        $this->filters['showDeactivated'] = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function showCore()
    {
        $this->filters['showCore'] = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function showNoCore()
    {
        $this->filters['showNoCore'] = true;
        return $this;
    }

    /**
     * @param string $nameOnly
     * @return array
     */
    public function getList($nameOnly = false)
    {
        return $this->getExtensions($this->extension, $nameOnly);
    }

    /**
     * @return $this
     */
    public function discoverPlugins($type = 'plugin')
    {
        $this->initialize();
        $this->discoverExtension($type);

        return $this;
    }

    /**
     * @return $this
     */
    public function discoverThemes()
    {
        $this->initialize();
        $this->discoverExtension('theme');

        return $this;
    }


    /**
     * @param string $extension
     */
    private function discoverExtension($extension)
    {
        $this->extension = $extension;
        $this->extensions[$extension] = $this->discoverExtensions($extension);
    }

    /**
     * initializeFilters
     */
    private function initialize()
    {
        $this->extension = 'plugin';
        $this->extensions = [
            'plugin' => [],
            'theme' => []
        ];
        $this->filters = [
            'showActivated' => false,
            'showDeactivated' => false
        ];
    }

    /**
     * @param string     $type
     * @param bool|false $nameOnly
     * @return array
     */
    private function getExtensions(
        $type = 'plugin',
        $nameOnly = false
    ) {
        $showActivated = $this->filters['showActivated'];
        $showDeactivated = $this->filters['showDeactivated'];

        $extensions = [];
        if (!array_key_exists($type, $this->extensions)) {
            return $this->extensions['type'];
        }

        foreach ($this->extensions[$type] as $extension => $extensionData) {
            $name = $extensionData['Name'];

            if ($type == 'plugin') {
                $isActivated = $this->site->isPluginActive($extension);
            } else {
                $isActivated = false;
            }

            if (!$showActivated && $isActivated) {
                continue;
            }
            if (!$showDeactivated && !$isActivated) {
                continue;
            }

            if ($nameOnly) {
                $extensions[$name] = $name;
            } else {
                $extensions[$extension] = $extensionData;
            }
        }

        return $nameOnly?array_keys($extensions):$extensions;
    }

    /**
     * @param string $type
     * @return \WP\Core\Extension\Extension[]
     */
    private function discoverExtensions($type)
    {
        if ($type === 'plugin') {
        }

        $discovery = new Discovery($this->site, $this->appRoot);
        $discovery->reset();

        return $discovery->scan($type);
    }

    /**
     * @param string $name
     * @return \WP\Console\Extension\Extension
     */
    public function getPlugin($name)
    {
        if ($extension = $this->getExtension('plugin', $name)) {
            return $this->createExtension($extension);
        }

        return null;
    }

    /**
     * @param string $name
     * @return \WP\Console\Extension\Extension
     */
    public function getTheme($name)
    {
        if ($extension = $this->getExtension('theme', $name)) {
            return $this->createExtension($extension);
        }

        return null;
    }

    /**
     * @param string $type
     * @param string $name
     *
     * @return \WP\Core\Extension\Extension
     */
    private function getExtension($type, $name)
    {
        if (!$this->extensions[$type]) {
            $this->discoverExtension($type);
        }

        $extensions = array_combine(array_keys($this->extensions[$type]), array_column($this->extensions[$type], 'Name'));
        if($extensionFilename = array_search($name, $extensions)) {
            $extension = array_merge(
                [
                    'type' => $type,
                    'filename' => basename($extensionFilename),
                    'pathname' => $extensionFilename
                ],
                $this->extensions[$type][$extensionFilename]
            );
            return $extension;
        }

        return null;
    }

    /**
     * @param \WP\Console\Core\Extension\Extension $extension
     * @return \WP\Console\Extension\Extension
     */
    private function createExtension($extension)
    {

        $consoleExtension = new Extension(
            ABSPATH,
            $extension['type'],
            $extension['pathname'],
            $extension['filename']
            /*$extension->getType(),
            $extension->getPathname(),
            $extension->getExtensionFilename()*/
        );

        //$consoleExtension->unserialize($extension);
        //$consoleExtension->unserialize($extension->serialize());

        return $consoleExtension;
    }

    /**
     * @param string   $testType
     * @param $fullPath
     * @return string
     */
    /*public function getTestPath($testType, $fullPath = false)
    {
        return $this->getPath($fullPath) . '/Tests/' . $testType;
    }*/

    /*public function validatePluginFunctionExist($moduleName, $function, $moduleFile = null)
    {
        //Load module file to prevent issue of missing functions used in update
        $module = $this->getModule($moduleName);
        $modulePath = $module->getPath();
        if ($moduleFile) {
            $this->site->loadLegacyFile($modulePath . '/'. $moduleFile);
        } else {
            $this->site->loadLegacyFile($modulePath . '/' . $module->getName() . '.module');
        }

        if (function_exists($function)) {
            return true;
        }
        return false;
    }*/
}

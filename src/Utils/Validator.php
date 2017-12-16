<?php

/**
 * @file
 * Contains \WP\Console\Utils\Validator.
 */

namespace WP\Console\Utils;

use WP\Console\Extension\Manager;

class Validator
{
    const REGEX_FUNCTION_NAME = '/^[a-z_\x7f-\xff]+$/';
    const REGEX_CLASS_NAME = '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]+$/';
    const REGEX_COMMAND_CLASS_NAME = '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]+Command$/';
    const REGEX_MACHINE_NAME = '/^[a-z0-9_]+$/';
    // This REGEX remove spaces between words
    const REGEX_REMOVE_SPACES = '/[\\s+]/';

    protected $appRoot;

    /**
     * Site constructor.
     *
     * @param Manager $extensionManager
     */
    public function __construct(Manager $extensionManager)
    {
        $this->extensionManager = $extensionManager;
    }

    public function validatePluginName($plugin)
    {
        if (!empty($plugin)) {
            return $plugin;
        } else {
            throw new \InvalidArgumentException(sprintf('Plugin name "%s" is invalid.', $plugin));
        }
    }

    public function validateFunctionName($function_name)
    {
        if (preg_match(self::REGEX_FUNCTION_NAME, $function_name)) {
            return $function_name;
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'Function name "%s" is invalid, it must starts with a lowercase letter or underscore, followed by any number of lowercase letters, or underscores.',
                    $function_name
                )
            );
        }
    }

    public function validateClassName($class_name)
    {
        if (preg_match(self::REGEX_CLASS_NAME, $class_name)) {
            return $class_name;
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'Class name "%s" is invalid, it must starts with a letter or underscore, followed by any number of letters, numbers, or underscores.',
                    $class_name
                )
            );
        }
    }

    public function validateCommandName($class_name)
    {
        if (preg_match(self::REGEX_COMMAND_CLASS_NAME, $class_name)) {
            return $class_name;
        } elseif (preg_match(self::REGEX_CLASS_NAME, $class_name)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Command name "%s" is invalid, it must end with the word \'Command\'',
                    $class_name
                )
            );
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'Command name "%s" is invalid, it must starts with a letter or underscore, followed by any number of letters, numbers, or underscores and then with the word \'Command\'.',
                    $class_name
                )
            );
        }
    }

    public function validateMachineName($machine_name)
    {
        if (preg_match(self::REGEX_MACHINE_NAME, $machine_name)) {
            return $machine_name;
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'Machine name "%s" is invalid, it must contain only lowercase letters, numbers and hyphens.',
                    $machine_name
                )
            );
        }
    }

    public function validatePluginPath($pluginPath, $create = false)
    {
        if (strlen($pluginPath) > 1 && $pluginPath[strlen($pluginPath)-1] == "/") {
            $pluginPath = substr($pluginPath, 0, -1);
        }
      
        if (is_dir($pluginPath)) {
            return $pluginPath;
        }

        if ($create && mkdir($pluginPath, 0755, true)) {
            chmod($pluginPath, 0755);
            return $pluginPath;
        }

        throw new \InvalidArgumentException(
            sprintf(
                'Path "%s" is invalid. You need to provide a valid path.',
                $pluginPath
            )
        );
    }

    /*public function validatePluginDependencies($dependencies)
    {
        $dependenciesChecked = [
            'success' => [],
            'fail' => [],
        ];

        if (empty($dependencies)) {
            return [];
        }

        $dependencies = explode(',', $this->removeSpaces($dependencies));
        foreach ($dependencies as $key => $plugin) {
            if (!empty($plugin)) {
                if (preg_match(self::REGEX_MACHINE_NAME, $plugin)) {
                    $dependenciesChecked['success'][] = $plugin;
                } else {
                    $dependenciesChecked['fail'][] = $plugin;
                }
            }
        }

        return $dependenciesChecked;
    }*/


    /**
     * Validates if class name have spaces between words.
     *
     * @param string $name
     *
     * @return string
     */
    public function validateSpaces($name)
    {
        $string = $this->removeSpaces($name);
        if ($string == $name) {
            return $name;
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'The name "%s" is invalid, spaces between words are not allowed.',
                    $name
                )
            );
        }
    }

    public function removeSpaces($name)
    {
        return preg_replace(self::REGEX_REMOVE_SPACES, '', $name);
    }

    /**
     * @param $pluginList
     * @return array
     */
    public function getMissingPlugins($pluginList)
    {
        $plugins = $this->extensionManager->discoverModules()
            ->showInstalled()
            ->showUninstalled()
            ->showNoCore()
            ->showCore()
            ->getList(true);

        return array_diff($pluginList, $plugins);
    }

    /**
     * @param $pluginList
     * @return array
     */
    public function getUninstalledPlugins($pluginList)
    {
        $plugins = $this->extensionManager->discoverModules()
            ->showInstalled()
            ->showNoCore()
            ->showCore()
            ->getList(true);

        return array_diff($pluginList, $plugins);
    }
}

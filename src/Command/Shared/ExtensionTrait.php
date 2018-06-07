<?php

/**
 * @file
 * Contains WP\Console\Command\Shared\ExtensionTrait.
 */

namespace WP\Console\Command\Shared;

/**
 * Class ExtensionTrait
 *
 * @package WP\Console\Command
 */
trait ExtensionTrait
{

    /**
     * @param string $extensionType
     *
     * @return string
     *
     * @throws \Exception
     */
    public function extensionQuestion($extensionType)
    {
        $plugins = [];
        $themes = [];
        if ($extensionType == 'plugin') {
            $plugins = $this->extensionManager->discoverPlugins()->showDeactivated()->showActivated()->getList(true);
        }

        if ($extensionType == 'theme') {
            $themes = $this->extensionManager->discoverThemes()->showDeactivated()->showActivated()->getList(true);
        }

        $extensions = array_merge(
            $plugins,
            $themes
        );

        if (empty($extensions)) {
            throw new \Exception('No extension available, execute the proper generator command to generate one.');
        }

        $extension = $this->getIo()->choiceNoList(
            $this->trans('commands.common.questions.extension'),
            $extensions
        );

        return $extension;
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    public function extensionTypeQuestion()
    {
        $extensionType = $this->getIo()->choiceNoList(
            $this->trans('commands.common.questions.extension-type'),
            ['plugin', 'theme']
        );

        return $extensionType;
    }
}

<?php

/**
 * @file
 * Contains WP\Console\Command\Shared\ExtensionTrait.
 */

namespace WP\Console\Command\Shared;

use WP\Console\Core\Style\WPStyle;

/**
 * Class ExtensionTrait
 *
 * @package WP\Console\Command
 */
trait ExtensionTrait
{

    /**
     * @param WPStyle   $io
     * @param bool|true $plugins
     * @param bool|true $theme
     * @param bool|true $profile
     *
     * @return string
     *
     * @throws \Exception
     */
    public function extensionQuestion(WPStyle $io, $extensionType)
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

        $extension = $io->choiceNoList(
            $this->trans('commands.common.questions.extension'),
            $extensions
        );

        return $extension;
    }

    /**
     * @param WPStyle $io
     *
     * @return string
     *
     * @throws \Exception
     */
    public function extensionTypeQuestion(WPStyle $io)
    {
        $extensionType = $io->choiceNoList(
            $this->trans('commands.common.questions.extension-type'),
            ['plugin', 'theme']
        );

        return $extensionType;
    }
}

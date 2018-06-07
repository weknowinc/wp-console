<?php

/**
 * @file
 * Contains WP\Console\Command\Shared\PluginTrait.
 */

namespace WP\Console\Command\Shared;

/**
 * Class PluginTrait
 *
 * @package WP\Console\Command
 */
trait ThemeTrait
{
    /**
     * @param string $status
     * @return string
     * @throws \Exception
     */
    public function themeQuestion($status = 'all')
    {
        $extensionManager = $this->extensionManager->discoverthemes();

        if ($status == 'all') {
            $extensionManager->showDeactivated()->showActivated();
        } elseif ($status) {
            $extensionManager->showActivated();
        } else {
            $extensionManager->showDeactivated();
        }

        $themes = $extensionManager->getList(true);

        if (empty($themes)) {
            throw new \Exception('No extension available, execute the proper generator command to generate one.');
        }

        $theme = $this->getIo()->choiceNoList(
            $this->trans('commands.common.questions.theme'),
            $themes
        );

        return $theme;
    }
}

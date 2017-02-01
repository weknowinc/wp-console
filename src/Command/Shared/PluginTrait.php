<?php

/**
 * @file
 * Contains WP\Console\Command\Shared\PluginTrait.
 */

namespace WP\Console\Command\Shared;

use WP\Console\Core\Style\WPStyle;

/**
 * Class PluginTrait
 *
 * @package WP\Console\Command
 */
trait PluginTrait
{
    /**
     * @param \WP\Console\Core\Style\WPStyle $io
     * @param all | bool $status
     * @return string
     * @throws \Exception
     */
    public function pluginQuestion(WPStyle $io, $status = 'all')
    {

        $extensionManager = $this->extensionManager->discoverPlugins();

        if($status == 'all') {
            $extensionManager->showDeactivated()->showActivated();
        } elseif($status) {
            $extensionManager->showActivated();
        } else {
            $extensionManager->showDeactivated();
        }

        $plugins = $extensionManager->getList(true);

        if (empty($plugins)) {
            throw new \Exception('No extension available, execute the proper generator command to generate one.');
        }

        $plugin = $io->choiceNoList(
            $this->trans('commands.common.questions.plugin'),
            $plugins
        );

        return $plugin;
    }
}

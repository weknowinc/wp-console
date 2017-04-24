<?php

/**
 * @file
 * Contains WP\Console\Command\Shared\ThemeTrait.
 */

namespace WP\Console\Command\Shared;

use WP\Console\Core\Style\WPStyle;

/**
 * Class ThemeTrait
 *
 * @package WP\Console\Command
 */
trait ThemeTrait
{
    /**
     * @param \WP\Console\Core\Style\WPStyle $io
     * @param all | bool $status
     * @return string
     * @throws \Exception
     */
    public function themeQuestion(WPStyle $io, $status = 'all')
    {
        
        $extensionManager = $this->extensionManager->discoverthemes();
        
        if($status == 'all') {
            $extensionManager->showDeactivated()->showActivated();
        } elseif($status) {
            $extensionManager->showActivated();
        } else {
            $extensionManager->showDeactivated();
        }
        
        $themes = $extensionManager->getList(true);
        
        if (empty($themes)) {
            throw new \Exception('No extension available, execute the proper generator command to generate one.');
        }
        
        $theme = $io->choiceNoList(
            $this->trans('commands.common.questions.theme'),
            $themes
        );
        
        return $theme;
    }
}

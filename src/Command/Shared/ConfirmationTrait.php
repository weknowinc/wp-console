<?php

/**
 * @file
 * Contains WP\Console\Command\Shared\ConfirmationTrait.
 */

namespace WP\Console\Command\Shared;

use WP\Console\Core\Style\WPStyle;

/**
 * Class ConfirmationTrait
 *
 * @package WP\Console\Command
 */
trait ConfirmationTrait
{
    /**
     * @param WPStyle $io
     * @param bool    $yes
     *
     * @return bool
     */
    public function confirmGeneration(WPStyle $io, $yes = false)
    {
        if ($yes) {
            return $yes;
        }

        $confirmation = $io->confirm(
            $this->trans('commands.common.questions.confirm'),
            true
        );

        if (!$confirmation) {
            $io->warning($this->trans('commands.common.messages.canceled'));
        }

        return $confirmation;
    }
}

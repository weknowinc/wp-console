<?php

/**
 * @file
 * Contains WP\Console\Command\Shared\ConfirmationTrait.
 */

namespace WP\Console\Command\Shared;

/**
 * Class ConfirmationTrait
 *
 * @package WP\Console\Command
 */
trait ConfirmationTrait
{
    /**
     * @return bool
     */
    public function confirmOperation()
    {
        $input = $this->getIo()->getInput();
        $yes = $input->hasOption('yes') ? $input->getOption('yes') : false;

        if ($yes) {
            return $yes;
        }

        $confirmation = $this->getIo()->confirm(
            $this->trans('commands.common.questions.confirm'),
            true
        );

        if (!$confirmation) {
            $this->getIo()->warning($this->trans('commands.common.messages.canceled'));
        }

        return $confirmation;
    }

    /**
     * @deprecated
     */
    public function confirmGeneration()
    {
        return $this->confirmOperation();
    }
}

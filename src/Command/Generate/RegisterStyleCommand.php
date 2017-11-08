<?php

/**
 * @file
 * Contains \WP\Console\Command\Generate\RegisterStyleCommand.
 */

namespace WP\Console\Command\Generate;

class RegisterStyleCommand extends RegisterBaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setRegisterType('style');
        $this->setCommandName('generate:register:style');
        $this->setAliases(['grst']);
        parent::configure();
    }
}

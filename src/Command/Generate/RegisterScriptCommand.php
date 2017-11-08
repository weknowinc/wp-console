<?php

/**
 * @file
 * Contains \WP\Console\Command\Generate\RegisterScriptCommand.
 */

namespace WP\Console\Command\Generate;

class RegisterScriptCommand extends RegisterBaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setRegisterType('script');
        $this->setCommandName('generate:register:script');
        $this->setAliases(['grsp']);
        parent::configure();
    }
}

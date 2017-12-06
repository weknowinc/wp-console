<?php

/**
 * @file
 * Contains \WP\Console\Command\Generate\CronSingleCommand.
 */

namespace WP\Console\Command\Generate;

class CronSingleCommand extends CronBaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setCronType('single');
        $this->setCommandName('generate:cron:single');
        $this->setAliases(['gcs']);
        parent::configure();
    }
}

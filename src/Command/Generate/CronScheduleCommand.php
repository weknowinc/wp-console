<?php

/**
 * @file
 * Contains \WP\Console\Command\Generate\CronScheduleCommand.
 */

namespace WP\Console\Command\Generate;

use Symfony\Component\Console\Input\InputOption;

class CronScheduleCommand extends CronBaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setCronType('schedule');
        $this->setCommandName('generate:cron:schedule');
        parent::configure();
        $this
            ->addOption(
                'recurrence',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.cron.schedule.options.recurrence')
            )
            ->setAliases(['gcsh']);
    }
}

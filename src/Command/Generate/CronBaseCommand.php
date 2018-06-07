<?php

/**
 * @file
 * Contains \WP\Console\Command\Generate\CronScheduleCommand.
 */

namespace WP\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use WP\Console\Command\Shared\PluginTrait;
use WP\Console\Command\Shared\ConfirmationTrait;
use WP\Console\Core\Command\Command;
use WP\Console\Generator\CronBaseGenerator;
use WP\Console\Core\Utils\StringConverter;
use WP\Console\Extension\Manager;
use WP\Console\Utils\Validator;

class CronBaseCommand extends Command
{
    use ConfirmationTrait;
    use PluginTrait;

    private $cronType;
    private $commandName;
    /**
     * @var CronBaseGenerator
     */
    protected $generator;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * CronBaseCommand constructor.
     *
     * @param CronBaseGenerator $generator
     * @param Manager           $extensionManager
     * @param Validator         $validator
     * @param StringConverter   $stringConverter
     */
    public function __construct(
        CronBaseGenerator $generator,
        Manager $extensionManager,
        Validator $validator,
        StringConverter $stringConverter
    ) {
        $this->generator = $generator;
        $this->extensionManager = $extensionManager;
        $this->validator = $validator;
        $this->stringConverter = $stringConverter;
        parent::__construct();
    }

    protected function setCronType($cronType)
    {
        return $this->cronType = $cronType;
    }

    protected function setCommandName($commandName)
    {
        return $this->commandName = $commandName;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName($this->commandName)
            ->setDescription($this->trans('commands.generate.cron.'.$this->cronType.'.description'))
            ->setHelp($this->trans('commands.generate.cron.'.$this->cronType.'.help'))
            ->addOption(
                'plugin',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.plugin')
            )
            ->addOption(
                'class-name',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.class-name')
            )
            ->addOption(
                'timestamp',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.cron.'.$this->cronType.'.options.timestamp')
            )
            ->addOption(
                'hook-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.cron.'.$this->cronType.'.options.hook-name')
            )
            ->addOption(
                'hook-arguments',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.cron.'.$this->cronType.'.options.hook-arguments')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $plugin = $input->getOption('plugin');
        $class_name = $this->validator->validateClassName($input->getOption('class-name'));
        $timestamp = $input->getOption('timestamp');
        $recurrence = $this->cronType == 'schedule' ? $input->getOption('recurrence'): null;
        $hook_name = $input->getOption('hook-name');
        $hook_arguments = $input->getOption('hook-arguments');

        // @see use WP\Console\Command\Shared\ConfirmationTrait::confirmOperation
        if (!$this->confirmOperation()) {
            return;
        }

        $this->generator->generate(
            $plugin,
            $class_name,
            $timestamp,
            $recurrence,
            $hook_name,
            $hook_arguments,
            $this->cronType
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // --plugin
        $plugin = $input->getOption('plugin');
        if (!$plugin) {
            $plugin = $this->pluginQuestion();
            $input->setOption('plugin', $plugin);
        }

        // --class name
        $class_name = $input->getOption('class-name');
        if (!$class_name) {
            $class_name = $this->getIo()->ask(
                $this->trans('commands.generate.cron.'.$this->cronType.'.questions.class-name'),
                'DefaultCron'.ucfirst($this->cronType),
                function ($class_name) {
                    return $this->validator->validateClassName($class_name);
                }
            );
            $input->setOption('class-name', $class_name);
        }

        // --timestamp
        $timestamp = $input->getOption('timestamp');
        if (!$timestamp) {
            if ($this->cronType == 'single') {
                $timestamp = $this->getIo()->ask(
                    $this->trans('commands.generate.cron.'.$this->cronType.'.questions.timestamp'),
                    null,
                    function ($timestamp) {
                        if (! (bool)strtotime($timestamp)) {
                            throw new \Exception($this->trans('commands.generate.cron.'.$this->cronType.'.errors.interval-invalid'));
                        }
                        return $timestamp;
                    }
                );
            } else {
                $timestamp = $this->getIo()->choice(
                    $this->trans('commands.generate.cron.'.$this->cronType.'.questions.timestamp'),
                    ['GMT Time', 'Local Time']
                );
            }
            $input->setOption('timestamp', $timestamp);
        }

        if ($this->cronType == 'schedule') {
            // --recurrence
            $recurrence = $input->getOption('recurrence');
            if (!$recurrence) {
                $recurrence = $this->getIo()->choice(
                    $this->trans('commands.generate.cron.'.$this->cronType.'.questions.recurrence'),
                    ['Hourly', 'Twice daily', 'Daily' ,'Custom']
                );

                if ($recurrence == 'Custom') {
                    $recurrence_name = $this->getIo()->ask(
                        $this->trans('commands.generate.cron.'.$this->cronType.'.questions.recurrence-name')
                    );
                    $recurrence_label = $this->getIo()->ask(
                        $this->trans('commands.generate.cron.'.$this->cronType.'.questions.recurrence-label')
                    );
                    $recurrence_interval = $this->getIo()->ask(
                        $this->trans('commands.generate.cron.'.$this->cronType.'.questions.recurrence-interval'),
                        null,
                        function ($recurrence_interval) {
                            if (!is_numeric($recurrence_interval)) {
                                throw new \Exception($this->trans('commands.generate.cron.'.$this->cronType.'.errors.interval-invalid'));
                            }
                            return $recurrence_interval;
                        }
                    );

                    $recurrence = [
                        "name" => $recurrence_name,
                        "label" => $recurrence_label,
                        "interval" => $recurrence_interval
                    ];
                }
                $input->setOption('recurrence', $recurrence);
            }
        }

        // --hook-name
        $hook_name = $input->getOption('hook-name');
        if (!$hook_name) {
            $hook_name = $this->getIo()->ask(
                $this->trans('commands.generate.cron.'.$this->cronType.'.questions.hook-name'),
                strtolower($class_name).'_hook'
            );
            $input->setOption('hook-name', $hook_name);
        }

        // --hook arguments
        $hook_arguments = $input->getOption('hook-arguments');
        if (!$hook_arguments) {
            $hook_arguments = $this->getIo()->askEmpty($this->trans('commands.generate.cron.'.$this->cronType.'.questions.hook-arguments'));

            $hook_arguments =  $hook_arguments == null ? $hook_arguments : explode(",", str_replace(" ", "", $hook_arguments));

            $input->setOption('hook-arguments', $hook_arguments);
        }
    }
}

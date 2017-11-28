<?php

/**
 * @file
 * Contains \WP\Console\Command\Generate\CronJobEventCommand.
 */

namespace WP\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use WP\Console\Command\Shared\PluginTrait;
use WP\Console\Command\Shared\ConfirmationTrait;
use WP\Console\Core\Command\Command;
use WP\Console\Generator\CronJobEventGenerator;
use WP\Console\Core\Utils\StringConverter;
use WP\Console\Extension\Manager;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Utils\Validator;

class CronJobEventCommand extends Command
{
    use ConfirmationTrait;
    use PluginTrait;

    /**
     * @var CronJobEventGenerator
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
     * CronJobEventCommand constructor.
     *
     * @param CronJobEventGenerator $generator
     * @param Manager               $extensionManager
     * @param Validator             $validator
     * @param StringConverter       $stringConverter
     */
    public function __construct(
        CronJobEventGenerator $generator,
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

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:cron:job:event')
            ->setDescription($this->trans('commands.generate.cron.job.event.description'))
            ->setHelp($this->trans('commands.generate.cron.job.event.help'))
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
                $this->trans('commands.generate.cron.job.event.options.timestamp')
            )
            ->addOption(
                'recurrence',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.cron.job.event.options.recurrence')
            )
            ->addOption(
                'hook-name',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.cron.job.event.options.hook-name')
            )
            ->addOption(
                'hook-arguments',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.cron.job.event.options.hook-arguments')
            )
            ->setAliases(['gcje']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);

        $plugin = $input->getOption('plugin');
        $class_name = $this->validator->validateClassName($input->getOption('class-name'));
        $timestamp = $input->getOption('timestamp');
        $recurrence = $input->getOption('recurrence');
        $hook_name = $input->getOption('hook-name');
        $hook_arguments = $input->getOption('hook-arguments');

        $yes = $input->hasOption('yes')?$input->getOption('yes'):false;

        // @see use WP\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io, $yes)) {
            return;
        }

        $this->generator->generate(
            $plugin,
            $class_name,
            $timestamp,
            $recurrence,
            $hook_name,
            $hook_arguments
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);

        // --plugin
        $plugin = $input->getOption('plugin');
        if (!$plugin) {
            $plugin = $this->pluginQuestion($io);
            $input->setOption('plugin', $plugin);
        }

        // --class name
        $class_name = $input->getOption('class-name');
        if (!$class_name) {
            $class_name = $io->ask(
                $this->trans('commands.generate.cron.job.event.questions.class-name'),
                'DefaultCronJobEvent',
                function ($class_name) {
                    return $this->validator->validateClassName($class_name);
                }
            );
            $input->setOption('class-name', $class_name);
        }

        // --timestamp
        $timestamp = $input->getOption('timestamp');
        if (!$timestamp) {
            $timestamp = $io->choice(
                $this->trans('commands.generate.cron.job.event.questions.timestamp'),
                ['GMT Time', 'Local Time']
            );
            $input->setOption('timestamp', $timestamp);
        }

        // --recurrence
        $recurrence = $input->getOption('recurrence');
        if (!$recurrence) {
            $recurrence = $io->choice(
                $this->trans('commands.generate.cron.job.event.questions.recurrence'),
                ['Hourly', 'Twice daily', 'Daily' ,'Custom']
            );

            if ($recurrence == 'Custom') {
                $recurrence_name = $io->ask($this->trans('commands.generate.cron.job.event.questions.recurrence-name'));
                $recurrence_label = $io->ask($this->trans('commands.generate.cron.job.event.questions.recurrence-label'));
                ;
                $recurrence_interval = $io->ask($this->trans('commands.generate.cron.job.event.questions.recurrence-interval'));
                ;

                $recurrence = [
                    "name" => $recurrence_name,
                    "label" => $recurrence_label,
                    "interval" => $recurrence_interval
                ];
            }
            $input->setOption('recurrence', $recurrence);
        }

        // --hook-name
        $hook_name = $input->getOption('hook-name');
        if (!$hook_name) {
            $hook_name = $io->ask(
                $this->trans('commands.generate.cron.job.event.questions.hook-name'),
                'custom_hook'
            );
            $input->setOption('hook-name', $hook_name);
        }

        // --hook arguments
        $hook_arguments = $input->getOption('hook-arguments');
        if (!$hook_arguments) {
            $hook_arguments = $io->askEmpty($this->trans('commands.generate.cron.job.event.questions.hook-arguments'));

            $hook_arguments =  $hook_arguments == null ? $hook_arguments : explode(",", str_replace(" ", "", $hook_arguments));

            $input->setOption('hook-arguments', $hook_arguments);
        }
    }
}

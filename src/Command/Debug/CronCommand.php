<?php

/**
 * @file
 * Contains \WP\Console\Command\CronEvent\DebugCommand.
 */

namespace WP\Console\Command\Debug;

use Faker\Provider\DateTime;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WP\Console\Core\Command\Command;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Utils\Site;

/**
 * Class CronEventCommand
 *
 * @package WP\Console\Command\Debug
 */
class CronCommand extends Command
{
    /**
     * @var Site
     */
    protected $site;

    /**
     * CronEventCommand constructor.
     *
     * @param Site $site
     */
    public function __construct(
        Site $site
    ) {
        $this->site = $site;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('debug:cron')
            ->setDescription($this->trans('commands.debug.cron.description'))
            ->addArgument(
                'type',
                InputArgument::OPTIONAL,
                $this->trans('commands.debug.cron.arguments.type')
            )
            ->setAliases(['dce']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);

        $type_cron = $input->getArgument('type');
        $type_cron = empty($type_cron) ? 'all' : $type_cron;

        if (!in_array($type_cron, ['single', 'schedule', 'all'])) {
            throw new \Exception($this->trans('commands.debug.cron.errors.invalid-argument'));
        }

        $this->site->loadLegacyFile('wp-includes/cron.php');

        $crons = [];

        //Get All crons
        foreach (_get_cron_array() as $time => $hooks) {
            foreach ($hooks as $hook => $hook_events) {
                foreach ($hook_events as $sig => $data) {
                    array_push(
                        $crons,
                        [
                            "name"     => $hook,
                            "time"     => $time,
                            'sig'      => $sig,
                            'args'     => $data['args'],
                            'schedule' => $this->changeScheduleToHuman($data['schedule']),
                            'interval' => $data['interval']
                        ]
                    );
                }
            }
        }

        //Filter crons by the argument
        foreach ($crons as $key => $value) {
            if ($type_cron == 'single') {
                if ($value["schedule"] != "Non-repeating") {
                    unset($crons[$key]);
                }
            }

            if ($type_cron == 'schedule') {
                if ($value["schedule"] == "Non-repeating") {
                    unset($crons[$key]);
                }
            }
        }
        $this->site->loadLegacyFile('wp-includes/formatting.php.');

        $tableHeader = [
            $type_cron == 'all' ? $this->trans('commands.debug.cron.messages.type') : null,
            $this->trans('commands.debug.cron.messages.name'),
            $this->trans('commands.debug.cron.messages.time'),
            $this->trans('commands.debug.cron.messages.next-time'),
            $this->trans('commands.debug.cron.messages.schedule'),
            $this->trans('commands.debug.cron.messages.interval'),
            $this->trans('commands.debug.cron.messages.args'),
        ];

        $tableRows = [];

        foreach ($crons as $cron) {
            $type = $cron["schedule"] == "Non-repeating" ? "Single" : "Schedule";

            $tableRows[] = [
                $type_cron == 'all' ? $type : null,
                $cron["name"],
                date('Y-m-d H:i:s', $cron["time"]),
                human_time_diff($cron["time"], time()),
                $cron["schedule"],
                $cron["interval"],
                implode(', ', $cron["args"])
            ];
        }

        $io->table($tableHeader, $tableRows);
    }

    /**
     * Change schedule to human formatter
     *
     * @param $schedule
     *
     * @return string
     */
    private function changeScheduleToHuman($schedule)
    {
        if ($schedule == "twicedaily") {
            return "Twice Daily";
        }

        if (!empty($schedule)) {
            return "Once ".ucfirst($schedule);
        }

        return "Non-repeating";
    }
}

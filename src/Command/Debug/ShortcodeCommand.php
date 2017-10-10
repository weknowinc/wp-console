<?php

/**
 * @file
 * Contains \WP\Console\Command\Debug\ShortcodeCommand.
 */

namespace WP\Console\Command\Debug;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WP\Console\Core\Command\Command;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Utils\Site;

/**
 * Class ShortcodeDebugCommand
 *
 * @package WP\Console\Command
 */
class ShortcodeCommand extends Command
{

    /**
     * @var Site
     */
    protected $site;

    /**
     * UsersCommand constructor.
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
            ->setName('debug:shortcode')
            ->setDescription($this->trans('commands.debug.shortcode.description'))
            ->setAliases(['ds']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);

        $tableHeader = [
            $this->trans('commands.debug.shortcode.messages.tag'),
            $this->trans('commands.debug.shortcode.messages.callback')
        ];

        $shortcodes = $this->site->getShortCodes();

        $rows = [];
        foreach ($shortcodes as $tag => $callback) {
            $rows[] = ['tag' => $tag , 'callback' => $callback];
        }

        $io->table($tableHeader, $rows, 'compact');

        return 0;
    }
}

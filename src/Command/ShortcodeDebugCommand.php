<?php

/**
 * @file
 * Contains \WP\Console\Command\ShortcodeDebugCommand.
 */

namespace WP\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Yaml\Yaml;
use WP\Console\Command\Shared\CommandTrait;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Utils\Site;

/**
 * Class ShortcodeDebugCommand
 *
 * @package WP\Console\Command
 */
class ShortcodeDebugCommand extends Command
{
    use CommandTrait;

    /**
     * @var Site
     */
    protected $site;

    /**
     * UsersCommand constructor.
     *
     * @param Site       $site
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
            ->setName('shortcode:debug')
            ->setDescription($this->trans('commands.shortcode.debug.description'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);

        $tableHeader = [
            $this->trans('commands.shortcode.debug.messages.tag'),
            $this->trans('commands.shortcode.debug.messages.callback')
        ];

        $shortcodes = $this->site->getShortCodes();

        $rows = [];
        foreach($shortcodes as $tag => $callback) {
            $rows[] = ['tag' => $tag , 'callback' => $callback];
        }

        $io->table($tableHeader, $rows, 'compact');

        return 0;
    }


}

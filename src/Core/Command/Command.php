<?php

/**
 * @file
 * Contains \WP\Console\Core\Command\Command.
 */

namespace WP\Console\Core\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;
use WP\Console\Core\Command\Shared\CommandTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WP\Console\Core\Style\WPStyle;

/**
 * Class Command
 *
 * @package WP\Console\Core\Command
 */
abstract class Command extends BaseCommand
{
    use CommandTrait;

    /**
     * @var WPStyle
     */
    private $io;

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new WPStyle($input, $output);
    }

    /**
     * @return \WP\Console\Core\Style\WPStyle
     */
    public function getIo()
    {
        return $this->io;
    }
}

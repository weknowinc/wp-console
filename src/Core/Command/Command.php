<?php

/**
 * @file
 * Contains \WP\Console\Core\Command\Command.
 */

namespace WP\Console\Core\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;
use WP\Console\Core\Command\Shared\CommandTrait;

/**
 * Class Command
 *
 * @package WP\Console\Core\Command
 */
abstract class Command extends BaseCommand
{
    use CommandTrait;
}

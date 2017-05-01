<?php

/**
 * @file
 * Contains \WP\Console\Command\Shared\DatabaseTrait.
 */

namespace WP\Console\Command\Shared;

use WP\Console\Core\Style\WPStyle;
use Symfony\Component\Console\Question\Question;

/**
 * Class DatabaseTrait
 *
 * @package WP\Console\Command\Shared
 */
trait DatabaseTrait
{
    /**
     * @param WPStyle $io
     *
     * @return mixed
     */
    public function dbHostQuestion(WPStyle $io)
    {
        return $io->ask(
            $this->trans('commands.site.install.questions.db-host'),
            '127.0.0.1'
        );
    }

    /**
     * @param WPStyle $io
     *
     * @return mixed
     */
    public function dbNameQuestion(WPStyle $io)
    {
        return $io->ask(
            $this->trans('commands.site.install.questions.db-name')
        );
    }

    /**
     * @param WPStyle $io
     *
     * @return mixed
     */
    public function dbUserQuestion(WPStyle $io)
    {
        return $io->ask(
            $this->trans('commands.site.install.questions.db-user')
        );
    }

    /**
     * @param WPStyle $io
     *
     * @return mixed
     */
    public function dbPassQuestion(WPStyle $io)
    {
        return $io->askHiddenEmpty(
            $this->trans('commands.site.install.questions.db-pass')
        );
    }

    /**
     * @param WPStyle $io
     *
     * @return mixed
     */
    public function dbPrefixQuestion(WPStyle $io)
    {
        $question = new Question($this->trans('commands.site.install.questions.db-prefix'), 'wp_');
        return trim($io->askQuestion($question));
    }

    /**
     * @param WPStyle $io
     *
     * @return mixed
     */
    public function dbPortQuestion(WPStyle $io)
    {
        return $io->ask(
            $this->trans('commands.site.install.questions.db-port'),
            '3306'
        );
    }
}

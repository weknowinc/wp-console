<?php

/**
 * @file
 * Contains \WP\Console\Command\Shared\DatabaseTrait.
 */

namespace WP\Console\Command\Shared;

use Symfony\Component\Console\Question\Question;

/**
 * Class DatabaseTrait
 *
 * @package WP\Console\Command\Shared
 */
trait DatabaseTrait
{
    /**
     * @return mixed
     */
    public function dbHostQuestion()
    {
        return $this->getIo()->ask(
            $this->trans('commands.site.install.questions.db-host'),
            '127.0.0.1'
        );
    }

    /**
     * @return mixed
     */
    public function dbNameQuestion()
    {
        return $this->getIo()->ask(
            $this->trans('commands.site.install.questions.db-name')
        );
    }

    /**
     * @return mixed
     */
    public function dbUserQuestion()
    {
        return $this->getIo()->ask(
            $this->trans('commands.site.install.questions.db-user')
        );
    }

    /**
     * @return mixed
     */
    public function dbPassQuestion()
    {
        return $this->getIo()->askHiddenEmpty(
            $this->trans('commands.site.install.questions.db-pass')
        );
    }

    /**
     * @return mixed
     */
    public function dbPrefixQuestion()
    {
        $question = new Question($this->trans('commands.site.install.questions.db-prefix'), 'wp_');
        return trim($this->getIo()->askQuestion($question));
    }

    /**
     * @return mixed
     */
    public function dbPortQuestion()
    {
        return $this->getIo()->ask(
            $this->trans('commands.site.install.questions.db-port'),
            '3306'
        );
    }
}

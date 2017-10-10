<?php

/**
 * @file
 * Contains WP\Console\Command\Shared\CommandTrait.
 */

namespace WP\Console\Core\Command\Shared;

use WP\Console\Core\Utils\TranslatorManager;

/**
 * Class CommandTrait
 *
 * @package WP\Console\Core\Command
 */
trait CommandTrait
{
    /**
     * @var  TranslatorManager
     */
    protected $translator;

    /**
     * @param $translator
     */
    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param $key string
     *
     * @return string
     */
    public function trans($key)
    {
        if (!$this->translator) {
            return $key;
        }

        return $this->translator->trans($key);
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        $description = sprintf(
            'commands.%s.description',
            str_replace(':', '.', $this->getName())
        );

        if (parent::getDescription()==$description) {
            return $this->trans($description);
        }

        return parent::getDescription();
    }
}

<?php

/**
 * @file
 * Contains WP\Console\Core\Command\ShowFileHelper.
 */

namespace WP\Console\Core\Utils;

use WP\Console\Core\Style\WPStyle;

/**
 * Class ShowFileHelper
 *
 * @package WP\Console\Core\Utils
 */
class ShowFile
{
    /**
     * @var string
     */
    protected $root;

    /**
     * @var TranslatorManager
     */
    protected $translator;

    /**
     * ShowFile constructor.
     *
     * @param string            $root
     * @param TranslatorManager $translator
     */
    public function __construct(
        $root,
        TranslatorManager $translator
    ) {
        $this->root = $root;
        $this->translator = $translator;
    }

    /**
     * @param WPStyle $io
     * @param string  $files
     * @param boolean $showPath
     */
    public function generatedFiles($io, $files, $showPath = true)
    {
        $pathKey = null;
        $path = null;
        if ($showPath) {
            $pathKey = 'application.user.messages.path';
            $path = $this->root;
        }
        $this->showMMultiple(
            $io,
            $files,
            'application.messages.files.generated',
            $pathKey,
            $path
        );
    }

    /**
     * @param WPStyle $io
     * @param string      $files
     * @param boolean     $showPath
     */
    public function copiedFiles($io, $files, $showPath = true)
    {
        $pathKey = null;
        $path = null;
        if ($showPath) {
            $pathKey = 'application.user.messages.path';
            $path = rtrim(getenv('HOME') ?: getenv('USERPROFILE'), '/\\').'/.console/';
        }
        $this->showMMultiple(
            $io,
            $files,
            'application.messages.files.copied',
            $pathKey,
            $path
        );
    }

    /**
     * @param WPStyle $io
     * @param array       $files
     * @param string      $headerKey
     * @param string      $pathKey
     * @param string      $path
     */
    private function showMMultiple($io, $files, $headerKey, $pathKey, $path)
    {
        if (!$files) {
            return;
        }

        $io->writeln($this->translator->trans($headerKey));

        if ($pathKey) {
            $io->info(
                sprintf('%s:', $this->translator->trans($pathKey)),
                false
            );
        }
        if ($path) {
            $io->comment($path, false);
        }
        $io->newLine();

        $index = 1;
        foreach ($files as $file) {
            $this->showSingle($io, $file, $index);
            ++$index;
        }
    }

    /**
     * @param WPStyle $io
     * @param string  $file
     * @param int     $index
     */
    private function showSingle(WPStyle $io, $file, $index)
    {
        $io->info(
            sprintf('%s -', $index),
            false
        );
        $io->comment($file, false);
        $io->newLine();
    }
}

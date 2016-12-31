<?php

namespace WP\Console\Helper;

class WordpressFinder
{
    /**
     * Wordpress public directory.
     *
     * @var string
     */
    private $wordpressRoot;


    public function locateRoot($start_path)
    {
        $this->wordpressRoot = false;

        foreach (array(true, false) as $follow_symlinks) {
            $path = $start_path;
            if ($follow_symlinks && is_link($path)) {
                $path = realpath($path);
            }
            // Check the start path.
            if ($this->isValidRoot($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $path
     *
     * @return bool
     */
    protected function isValidRoot($path)
    {
        // Validate that Wordpress load files is available
        if (!empty($path) && is_dir($path) && file_exists($path . '/wp-load.php')) {
             $this->wordpressRoot = $path;

        } else {
            $this->wordpressRoot = false;
        }
        return (bool) $this->wordpressRoot;
    }

    /**
     * @return string
     */
    public function getWordpressRoot()
    {
        return $this->wordpressRoot;
    }
}

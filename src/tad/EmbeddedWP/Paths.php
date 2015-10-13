<?php

namespace tad\EmbeddedWP;


use tad\WPBrowser\Utils\PathUtils;

class Paths implements PathFinder
{
    /**
     * @var string
     */
    private $rootDir;

    public function __construct($rootDir = null)
    {
        $this->rootDir = $rootDir ?: __DIR__;
    }

    /**
     * Returns the path to the embedded WP installation root folder.
     *
     * @return string
     */
    public function getWpRootFolder()
    {
        return PathUtils::findHereOrInParent('embedded-wordpress', $this->rootDir);
    }

    public function getWPContentFolder()
    {
        return $this->getWpRootFolder() . '/wp-content';
    }

    public function getWPThemesFolder()
    {
        return $this->getWPContentFolder() . '/themes';
    }

    public function getWPMuPluginsFolder()
    {
        return $this->getWPContentFolder() . '/mu-plugins';
    }

    public function getWpPluginsFolder()
    {
        return $this->getWPContentFolder() . '/plugins';
    }

    /**
     * @return string
     */
    public function getRootDir()
    {
        return $this->rootDir;
    }
}
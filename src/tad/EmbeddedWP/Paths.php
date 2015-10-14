<?php

namespace tad\EmbeddedWP;


use tad\WPBrowser\Utils\PathUtils;

class Paths implements PathFinder
{
    protected $wpRootFolder;
    private $rootDir;

    public function __construct($rootDir = null,
        $wpRootFolder = null)
    {
        $this->rootDir = $rootDir ?: __DIR__;
        $this->wpRootFolder = $wpRootFolder ?: PathUtils::findHereOrInParent('embedded-wordpress', $this->rootDir);
    }

    public function getWPThemesFolder()
    {
        return $this->getWPContentFolder() . '/themes';
    }

    public function getWPContentFolder()
    {
        return $this->getWpRootFolder() . '/wp-content';
    }

    /**
     * Returns the path to the embedded WP installation root folder.
     *
     * @return string
     */
    public function getWpRootFolder()
    {
        return $this->wpRootFolder;
    }

    public function setWPRootFolder($wpRootFolder)
    {
        $this->wpRootFolder = $wpRootFolder;
        return $this;
    }

    public function getWPMuPluginsFolder()
    {
        return $this->getWPContentFolder() . '/mu-plugins';
    }

    public function getWpPluginsFolder()
    {
        return $this->getWPContentFolder() . '/plugins';
    }

    public function getRootDir()
    {
        return $this->rootDir;
    }
}
<?php

namespace tad\EmbeddedWP;

interface PathFinder
{
    /**
     * Returns the path to the embedded WP installation root folder.
     *
     * @return string
     */
    public function getWpRootFolder();

    public function getWPContentFolder();

    public function getWPThemesFolder();

    public function getWPMuPluginsFolder();

    public function getWpPluginsFolder();

    public function getRootDir();
}
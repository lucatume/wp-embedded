<?php

namespace tad\EmbeddedWP;

use Codeception\Exception\ModuleConfigException;
use tad\WPBrowser\Utils\PathUtils;

class MainPluginLoader extends PluginLoader
{
    /**
     * MainPluginLoader constructor.
     * @param $mainFile
     * @param PathFinder|Paths $pathFinder
     * @param \Symfony\Component\Filesystem\Filesystem $filesystem
     */
    public function __construct($mainFile,
        $pathFinder,
        $filesystem)
    {
        $path = $this->getMainPluginFileAbspath($mainFile, $pathFinder->getRootDir());
        parent::__construct($path, $pathFinder, $filesystem);
    }

    /**
     * @return string
     * @throws ModuleConfigException
     */
    protected function getMainPluginFileAbspath($mainFile,
        $rootDir)
    {
        $mainFile = PathUtils::unleadslashit($mainFile);
        if (!file_exists($mainFile)) {
            $path = $rootDir . DIRECTORY_SEPARATOR . $mainFile;
        } else {
            $path = $mainFile;
        }
        if (!file_exists($path)) {
            throw new ModuleConfigException(__CLASS__, "The '{$mainFile}' file was not found in the '{$path}' path; this might be due to a wrong configuration of the `mainFile` setting.");
        }
        return $path;
    }
}
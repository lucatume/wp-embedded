<?php

namespace tad\EmbeddedWP;

use Codeception\Exception\ModuleConfigException;
use tad\WPBrowser\Filesystem;
use tad\WPBrowser\Utils\PathUtils;

class MainPluginLoader extends PluginLoader
{
    /**
     * @param $mainFile
     * @param PathFinder $pathFinder
     * @param Filesystem $filesystem
     * @throws ModuleConfigException
     */
    public function __construct($mainFile,
        PathFinder $pathFinder,
        Filesystem $filesystem)
    {
        if (!is_string($mainFile)) {
            throw new ModuleConfigException(__CLASS__, 'Main file setting must be a string');
        }
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
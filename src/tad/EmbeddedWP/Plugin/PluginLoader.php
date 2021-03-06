<?php

namespace tad\EmbeddedWP\Plugin;


use Codeception\Exception\ModuleConfigException;
use tad\WPBrowser\Filesystem\Filesystem;
use tad\WPBrowser\Filesystem\PathFinder;
use tad\WPBrowser\Filesystem\Utils;

class PluginLoader
{
    /**
     * @var string The absolute path to the plugin main file.
     */
    protected $path;
    /**
     * @var PathFinder
     */
    protected $pathFinder;
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @param $pluginPath
     * @param PathFinder $pathFinder
     * @param Filesystem $filesystem
     * @throws ModuleConfigException
     */
    public function __construct($pluginPath,
        PathFinder $pathFinder,
        Filesystem $filesystem)
    {
        if (!is_string($pluginPath)) {
            throw new ModuleConfigException(__CLASS__, 'Plugin paht must be a string.');
        }
        $this->pathFinder = $pathFinder;
        $this->filesystem = $filesystem;
        if (!file_exists($pluginPath)) {
            // relative path to required plugin
            $path = $this->pathFinder->getRootFolder() . DIRECTORY_SEPARATOR . Utils::unleadslashit($pluginPath);
        } else {
            // absolute path to required plugin
            $path = $pluginPath;
        }
        // require the plugin files
        if (!(file_exists($path) && is_file($path))) {
            throw new ModuleConfigException(__CLASS__, "The required plugin file '{$path}' does not exist; required plugins paths should be relative to the project root folder or absolute paths and point to a plugin file.");
        }
        $this->path = $path;
        $this->pluginFolder = basename(dirname($path));
    }

    public function requireIt()
    {
        $this->filesystem->requireOnce($this->path);
    }

    public function symlinkIt()
    {

        $linkDestination = $this->pathFinder->getWpPluginsFolder() . "/{$this->pluginFolder}";
        if (!$this->filesystem->exists($linkDestination)) {
            $from = dirname($this->path);
            $this->filesystem->symlink($from, $linkDestination);
        }
    }
}

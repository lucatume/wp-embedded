<?php

namespace tad\EmbeddedWP\Plugin;


use Codeception\Exception\ModuleConfigException;
use tad\WPBrowser\Filesystem\PathFinder;
use tad\WPBrowser\Filesystem\Utils;

class PluginActivation
{
    /**
     * @var string
     */
    protected $pluginBasename;
    /**
     * @var string
     */
    protected $mainFile;
    /**
     * @var array
     */
    private $config;
    /**
     * @var PathFinder
     */
    private $pathFinder;

    /**
     * PluginActivation constructor.
     * @param string $pluginBasename
     * @param array $config
     * @throws ModuleConfigException
     */
    public function __construct($pluginBasename,
        array $config,
        PathFinder $pathFinder)
    {
        if (!is_string($pluginBasename)) {
            throw new ModuleConfigException(__CLASS__, 'Plugin basename must be a string.');
        }
        $this->config = $config;
        $this->pathFinder = $pathFinder;
        $this->mainFile = empty($this->config['mainFile']) ? false : $this->config['mainFile'];
        $pluginBasename = $this->maybeCastMainPluginFile($pluginBasename);
        $pluginBasename = $this->ensurePluginBasenamePattern($pluginBasename);
        $this->pluginBasename = $pluginBasename;
    }

    /**
     * @param $pluginBasename
     * @return string
     */
    protected function maybeCastMainPluginFile($pluginBasename)
    {
        if ($this->mainFile) {
            if ($pluginBasename === Utils::unleadslashit($this->mainFile)) {
                $pluginBasename = basename($this->pathFinder->getRootFolder()) . DIRECTORY_SEPARATOR . $pluginBasename;
                return $pluginBasename;
            }
        }
        return $pluginBasename;
    }

    /**
     * @param $pluginBasenameCandidate
     * @throws ModuleConfigException
     */
    protected function ensurePluginBasenamePattern($pluginBasenameCandidate)
    {
        if (is_file($pluginBasenameCandidate)) {
            $pluginBasename = basename(dirname($pluginBasenameCandidate)) . DIRECTORY_SEPARATOR . basename($pluginBasenameCandidate);
        } else {
            $pluginBasenameCandidate = Utils::unleadslashit($pluginBasenameCandidate);
            $pattern = "~^[\\w\\d-_^\\s]*(\\/{1}[\\w\\d-_^\\s]*){0,1}.php$~u";
            if (!preg_match($pattern, $pluginBasenameCandidate)) {
                throw new ModuleConfigException(__CLASS__, "Format for `activatePlugins` entries should be 'pluginFolder/pluginFile.php' or 'single-file.php' format, {$pluginBasenameCandidate} is not valid.");
            }
            $pluginBasename = $pluginBasenameCandidate;
        }

        return $pluginBasename;
    }

    /**
     * Calls the activation action on the plugin
     */
    public function activate()
    {
        do_action("activate_{$this->pluginBasename}");
    }
}
<?php

namespace tad\EmbeddedWP;


use Codeception\Exception\ModuleConfigException;
use tad\WPBrowser\Utils\PathUtils;

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
     * PluginActivation constructor.
     * @param string $pluginBasename
     * @param array $config
     * @throws ModuleConfigException
     */
    public function __construct($pluginBasename,
        $config)
    {
        $this->config = $config;
        $this->mainFile = $this->config['mainFile'];
        $pluginBasename = $this->maybeCastMainPluginFile($pluginBasename);
        $pluginBasename = PathUtils::unleadslashit($pluginBasename);
        $this->ensurePluginBasenamePattern($pluginBasename);
        $this->pluginBasename = $pluginBasename;
    }

    /**
     * @param $pluginBasename
     * @return string
     */
    protected function maybeCastMainPluginFile($pluginBasename)
    {
        if ($pluginBasename === PathUtils::unleadslashit($this->mainFile)) {
            $pluginBasename = basename($this->config['_rootDir']) . DIRECTORY_SEPARATOR . $pluginBasename;
            return $pluginBasename;
        }
        return $pluginBasename;
    }

    /**
     * @param $pluginBasenameCandidate
     * @throws ModuleConfigException
     */
    protected function ensurePluginBasenamePattern($pluginBasenameCandidate)
    {
        $pattern = "~^[A-Za-z0-9-_]{1}[/a-zA-Z0-9-_]*\\.php$~u";
        if (!preg_match($pattern, $pluginBasenameCandidate)) {
            throw new ModuleConfigException(__CLASS__, "Format for `activatePlugins` entries should be 'pluginFolder/pluginFile.php' or 'single-file.php' ([a-rA-Z0-9-_] pattern allowed), {$pluginBasenameCandidate} is not valid.");
        }
    }

    /**
     * Calls the activation action on the plugin
     */
    public function activate()
    {
        do_action("activate_{$this->pluginBasename}");
    }
}
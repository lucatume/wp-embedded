<?php

namespace Codeception\Module;

use Codeception\Exception\ModuleConfigException;
use tad\WPBrowser\Utils\PathUtils;

class EmbeddedWP extends WPLoader
{
    protected $requiredFields = array('mainFile');

    public function activatePlugins()
    {
        if (empty($this->config['activatePlugins'])) {
            return;
        }
        foreach ($this->config['activatePlugins'] as $plugin) {
            if ($plugin == $this->config['mainFile']) {
                $plugin = basename(codecept_root_dir()) . DIRECTORY_SEPARATOR . PathUtils::unleadslashit($plugin);
            }
            // @todo required plugins
            do_action("activate_$plugin");
        }
    }

    protected $config = array('dbDir' => false,
        'dbFile' => 'wordpress',
        'wpDebug' => true,
        'multisite' => false,
        'dbCharset' => 'utf8',
        'dbCollate' => '',
        'tablePrefix' => 'wptests_',
        'domain' => 'example.org',
        'adminEmail' => 'admin@example.org',
        'title' => 'Test Blog',
        'phpBinary' => 'php',
        'language' => '',
        'requiredPlugins' => array(),
        'mainFile' => '',
        'activatePlugins' => '',
        'bootstrapActions' => '');

    protected function getWpRootFolder()
    {
        return dirname(dirname(dirname(__FILE__))) . '/embedded-wordpress/';
    }

    public function loadPlugins()
    {
        $this->loadRequiredPlugins();
        $this->loadMainPlugin();
    }

    protected function defineGlobals()
    {
        $wpRootFolder = $this->getWpRootFolder();

        // load an extra config file if any
        $this->loadConfigFile($wpRootFolder);

        $constants = array('ABSPATH' => $wpRootFolder,
            'DB_NAME' => 'spoof',
            'DB_USER' => 'spoof',
            'DB_PASSWORD' => 'spoof',
            'DB_HOST' => 'spoof',
            'DB_FILE' => $this->config['dbFile'],
            'DB_DIR' => $this->config['dbDir'] ? $this->config['dbDir'] : $wpRootFolder,
            'DB_CHARSET' => $this->config['dbCharset'],
            'DB_COLLATE' => $this->config['dbCollate'],
            'WP_TESTS_TABLE_PREFIX' => $this->config['tablePrefix'],
            'WP_TESTS_DOMAIN' => $this->config['domain'],
            'WP_TESTS_EMAIL' => $this->config['adminEmail'],
            'WP_TESTS_TITLE' => $this->config['title'],
            'WP_PHP_BINARY' => $this->config['phpBinary'],
            'WPLANG' => $this->config['language'],
            'WP_DEBUG' => $this->config['wpDebug'],
            'WP_TESTS_MULTISITE' => $this->config['multisite']);

        foreach ($constants as $key => $value) {
            if (!defined($key)) {
                define($key, $value);
            }
        }

        // spoof plugins config value
        $this->config['plugins'] = [$this->config['mainFile']];
    }

    private function loadMainPlugin()
    {
        $mainFile = $this->config['mainFile'];
        $path = $this->mainPluginBasename($mainFile);
        if (!file_exists($path)) {
            throw new ModuleConfigException(__CLASS__, "The '{$mainFile}' file was not found in the root project directory; this might be due to a wrong configuration of the `mainFile` setting.");
        }
        $this->linkPluginPath($path);
        require_once $path;
    }

    /**
     * @param $path
     */
    private function linkPluginPath($path)
    {
        $linkedPath = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $path;
        if (!file_exists($linkedPath)) {
            link($path, $linkedPath);
        }
    }

    /**
     * @param $mainFile
     * @return string
     */
    private function mainPluginBasename($mainFile)
    {
        $path = basename(codecept_root_dir()) . DIRECTORY_SEPARATOR . PathUtils::unleadslashit($mainFile);
        return $path;
    }

    private function loadRequiredPlugins()
    {
        $requiredPlugins = $this->config['requiredPlugins'];
        foreach ($requiredPlugins as $requiredPlugin) {
            $path = codecept_root_dir(PathUtils::unleadslashit($requiredPlugin));
            if (!file_exists($path)) {
                throw new ModuleConfigException(__CLASS__, "The '{$requiredPlugin}' file was not found in relation to tthe root project directory; this might be due to a wrong configuration of the `mainFile` setting.");
            }
            $this->linkPluginPath($path);
            require_once $path;
        }
    }
}
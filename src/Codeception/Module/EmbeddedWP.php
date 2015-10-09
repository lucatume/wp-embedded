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
        $sep = DIRECTORY_SEPARATOR;
        $pattern = "~^[A-Za-z_-][A-Za-z0-9_-]*{$sep}[A-Za-z0-9_-]*\\.php$~u";
        foreach ($this->config['activatePlugins'] as $plugin) {
            if ($plugin === PathUtils::unleadslashit($this->config['mainFile'])) {
                $plugin = basename(codecept_root_dir()) . DIRECTORY_SEPARATOR . $plugin;
            }
            $plugin = PathUtils::unleadslashit($plugin);
            if (!preg_match($pattern, $plugin)) {
                throw new ModuleConfigException(__CLASS__, "Format for `activatePlugins` entries should be 'pluginFolder/pluginFile.php' ([a-zA-Z0-9-_] pattern allowed), {$plugin} is not valid.");
            }
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
        'config_file' => '',
        'mainFile' => '',
        'requiredPlugins' => [],
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

    private function symlinkPlugin($from, $pluginFolder)
    {
        $linkDestination = $this->getWpRootFolder() . '/wp-content/plugins/' . $pluginFolder;
        if (!file_exists($linkDestination)) {
            symlink($from, $linkDestination);
        }
    }

    private function loadMainPlugin()
    {
        if (empty($this->config['mainFile'])) {
            return;
        }
        $mainFile = PathUtils::unleadslashit($this->config['mainFile']);
        $realPath = realpath(codecept_root_dir(DIRECTORY_SEPARATOR . $mainFile));
        if (!file_exists($realPath)) {
            throw new ModuleConfigException(__CLASS__, "The '{$mainFile}' file was not found in the '{$realPath}' path; this might be due to a wrong configuration of the `mainFile` setting.");
        }
        /** @noinspection PhpIncludeInspection */
        require_once $realPath;
        $pluginFolder = basename(codecept_root_dir());
        $this->symlinkPlugin(codecept_root_dir(), $pluginFolder);
    }

    private function loadRequiredPlugins()
    {
        if (empty($this->config['requiredPlugins'])) {
            return;
        }
        $requiredPlugins = $this->config['requiredPlugins'];
        foreach ($requiredPlugins as $requiredPlugin) {
            if (!file_exists($requiredPlugin)) {
                // relative path to required plugin
                $path = realpath(codecept_root_dir(DIRECTORY_SEPARATOR . PathUtils::unleadslashit($requiredPlugin)));
            } else {
                // absolute path to required plugin
                $path = $requiredPlugin;
            }
            // require the plugin files
            if (!file_exists($path)) {
                throw new ModuleConfigException(__CLASS__, "The required plugin file '{$path}' does not exist; required plugins paths should be relative to the project root folder or absolute paths");
            }
            /** @noinspection PhpIncludeInspection */
            require_once $path;
            // `/Users/Me/Plugins/my-plugin/my-plugin.php` to `my-plugin`
            $pluginFolder = basename(dirname($path));
            $this->symlinkPlugin(dirname($path), $pluginFolder);
        }
    }

    protected function setActivePlugins()
    {
        $activePlugins = [$this->getMainPluginBasename()];
        if (!empty($this->config['requiredPlugins'])) {
            $requiredPlugins = is_array($this->config['requiredPlugins']) ? $this->config['requiredPlugins'] : [$this->config['requiredPlugins']];
            foreach ($requiredPlugins as $plugin) {
                $pluginBasename = basename(dirname($plugin)) . DIRECTORY_SEPARATOR . basename($plugin);
                $activePlugins[] = $pluginBasename;
            }
        }
        if (!empty($GLOBALS['wp_tests_options']['active_plugins'])) {
            $GLOBALS['wp_tests_options']['active_plugins'] = array_merge($GLOBALS['wp_tests_options']['active_plugins'], $activePlugins);
        } else {
            $GLOBALS['wp_tests_options']['active_plugins'] = $activePlugins;
        }
    }

    protected function getMainPluginBasename()
    {
        return basename(codecept_root_dir()) . DIRECTORY_SEPARATOR . PathUtils::unleadslashit($this->config['mainFile']);
    }
}
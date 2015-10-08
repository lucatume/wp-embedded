<?php

namespace Codeception\Module;

use Codeception\Exception\ModuleConfigException;

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
                $plugin = basename(getcwd()) . DIRECTORY_SEPARATOR . $plugin;
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
        'mainFile' => '',
        'activatePlugins' => '',
        'bootstrapActions' => '');

    protected function getWpRootFolder()
    {
        return dirname(dirname(dirname(__FILE__))) . '/embedded-wordpress/';
    }

    public function loadPlugins()
    {
        if (empty($this->config['mainFile'])) {
            return;
        }
        $mainFile = $this->config['mainFile'];
        $path = getcwd() . DIRECTORY_SEPARATOR . $mainFile;
        if (!file_exists($path)) {
            throw new ModuleConfigException(__CLASS__, "The '{$mainFile}' file was not found in the root project directory; this might be due to a wrong configuration of the `mainFile` setting.");
        }
        require_once $path;
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
            'WP_PLUGIN_DIR' => dirname(getcwd()),
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
}
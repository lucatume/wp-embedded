<?php

namespace Codeception\Module;


use tad\WPBrowser\Utils\PathUtils;

class EmbeddedWPLoader extends WPLoader
{
    protected $requiredFields = array();

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
        'plugins' => '',
        'activatePlugins' => '',
        'bootstrapActions' => '');

    protected function getWpRootFolder()
    {
        return dirname(dirname(dirname(__FILE__))) . '/embedded-wordpress';
    }

    protected function defineGlobals()
    {
        $wpRootFolder = $this->getWpRootFolder();

        // load an extra config file if any
        $this->loadConfigFile($wpRootFolder);

        $constants = array('ABSPATH' => $wpRootFolder,
            'DB_NAME' => 'notApplicable',
            'DB_USER' => 'notApplicable',
            'DB_PASSWORD' => 'notApplicable',
            'DB_HOST' => 'notApplicable',
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
    }

}
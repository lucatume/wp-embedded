<?php

namespace Codeception\Module;

use Codeception\Exception\ModuleConfigException;
use Codeception\Lib\ModuleContainer;
use tad\EmbeddedWP\MainPluginLoader;
use tad\EmbeddedWP\PathFinder;
use tad\EmbeddedWP\Paths;
use tad\EmbeddedWP\PluginLoader;
use tad\WPBrowser\Utils\PathUtils;

class EmbeddedWP extends WPLoader
{
    protected $requiredFields = array('mainFile');
    protected $config = array(
        'dbDir' => false,
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
        'bootstrapActions' => ''
    );

    /**
     * @var  string The absolute path to the main plugin file.
     */
    protected $mainPluginFileAbspath;

    /**
     * @var PathFinder
     */
    private $pathFinder;
    /**
     * @var \tad\WPBrowser\Filesystem
     */
    private $filesystem;

    /**
     * @param ModuleContainer $moduleContainer
     * @param null $config
     * @param PathFinder|null $pathFinder
     * @param \tad\WPBrowser\Filesystem|null $filesystem
     */
    public function __construct(ModuleContainer $moduleContainer,
        $config = null,
        PathFinder $pathFinder = null,
        \tad\WPBrowser\Filesystem $filesystem = null)
    {
        parent::__construct($moduleContainer, $config);
        $this->pathFinder = $pathFinder ?: new Paths(codecept_root_dir());
        $this->filesystem = $filesystem ?: new \tad\WPBrowser\Filesystem();
    }

    /**
     * Calls the `activate_{$plugin}` hook for each plugin that requires activation.
     *
     * @throws ModuleConfigException If any of the specififed plugins to activate doesn't exist.
     *
     * @return void
     */
    public function activatePlugins()
    {
        if (empty($this->config['activatePlugins'])) {
            return;
        }

        $activatePlugins = (array)$this->config['activatePlugins'];
        $this->config['_rootDir'] = $this->pathFinder->getRootDir();

        foreach ($activatePlugins as $plugin) {
            $activationCandidate = new \tad\EmbeddedWP\PluginActivation($plugin, $this->config);
            $activationCandidate->activate();
        }
    }

    /**
     * Loads the required plugins main files and the plugin main file.
     *
     * @throws ModuleConfigException If any of the specified files doesn't exist
     *
     * @return void
     */
    public function loadPlugins()
    {
        $this->loadRequiredPlugins();
        $this->loadMainPlugin();
    }

    /**
     * Requires the main file of each specified required plugin.
     *
     * @throws ModuleConfigException If the specified plugin file doesn't exist.
     *
     * @return void
     */
    public function loadRequiredPlugins()
    {
        if (empty($this->config['requiredPlugins'])) {
            return;
        }

        $requiredPlugins = (array)$this->config['requiredPlugins'];
        foreach ($requiredPlugins as $requiredPlugin) {
            $pluginLoader = new PluginLoader($requiredPlugin, $this->pathFinder, $this->filesystem);
            $pluginLoader->requireIt();
            $pluginLoader->symlinkIt();
        }
    }

    /**
     * Requires the main plugin file and symlinks it in the embedded
     * WP installation folder.
     *
     * @throws ModuleConfigException If the specified main plugin file was not found
     * in the the project root folder.
     *
     * @return void
     */
    public function loadMainPlugin()
    {
        if (empty($this->config['mainFile'])) {
            return;
        }
        $pluginLoader = new MainPluginLoader($this->config['mainFile'], $this->pathFinder, $this->filesystem);
        $pluginLoader->requireIt();
        $pluginLoader->symlinkIt();
    }

    /**
     * Defines the global constants that will be used by the embedded WP installation.
     *
     * @throws ModuleConfigException If a specified additional config file doesn't exist.
     *
     * @return void
     */
    protected function defineGlobals()
    {
        $wpRootFolder = $this->pathFinder->getWpRootFolder();

        // load an extra config file if any
        $this->loadConfigFile($wpRootFolder);

        $constants = array(
            'ABSPATH' => $wpRootFolder,
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
            'WP_TESTS_MULTISITE' => $this->config['multisite']
        );

        foreach ($constants as $key => $value) {
            if (!defined($key)) {
                define($key, $value);
            }
        }
    }

    /**
     * Sets the value of the `active_plugins` option.
     *
     * @return void
     */
    protected function setActivePlugins()
    {
        $activePlugins = [];
        if (!empty($this->config['mainFile'])) {
            $activePlugins[] = $this->getMainPluginBasename();
        }
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

    /**
     * Returns the main plugin base name in a `dir/file.php` fashion.
     *
     * @return string
     */
    protected function getMainPluginBasename()
    {
        return basename(codecept_root_dir()) . DIRECTORY_SEPARATOR . PathUtils::unleadslashit($this->config['mainFile']);
    }
}
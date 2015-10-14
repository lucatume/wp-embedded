<?php
namespace Codeception\Module;


use org\bovigo\vfs\vfsStream;
use tad\EmbeddedWP\Paths;
use tad\FunctionMocker\FunctionMocker as Test;

class EmbeddedWPTest extends \Codeception\TestCase\Test
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @test
     * it should not do activation action if no plugins to activate
     */
    public function it_should_not_do_activation_action_if_no_plugins_to_activate()
    {
        $sut = new EmbeddedWP(make_container(), []);
        $do_action = Test::replace('do_action');

        $sut->activatePlugins();

        $do_action->wasNotCalled();
    }

    /**
     * @test
     * it should call activate action if one plugin to activate
     */
    public function it_should_call_activate_action_if_one_plugin_to_activate()
    {
        $pluginSlug = 'some-plugin/some-file.php';
        $config = ['activatePlugins' => $pluginSlug];
        $sut = new EmbeddedWP(make_container(), $config);
        $do_action = Test::replace('do_action');

        $sut->activatePlugins();

        $do_action->wasCalledWithOnce(["activate_{$pluginSlug}"]);
    }

    /**
     * @test
     * it should call activate action once for each plugin to activate
     */
    public function it_should_call_activate_action_once_for_each_plugin_to_activate()
    {
        $plugins = [
            'plugin-a/plugin-a.php',
            'plugin-b/plugin-b.php',
            'plugin-c/plugin-c.php'
        ];
        $sut = new EmbeddedWP(make_container(), ['activatePlugins' => $plugins]);
        $do_action = Test::replace('do_action');

        $sut->activatePlugins();

        $do_action->wasCalledTimes(count($plugins));
        foreach ($plugins as $plugin) {
            $do_action->wasCalledWithOnce(['activate_' . $plugin]);
        }
    }

    /**
     * @test
     * it should allow the user to activate single file plugins
     */
    public function it_should_allow_the_user_to_activate_single_file_plugins()
    {
        $pluginSlug = 'some-file.php';
        $config = ['activatePlugins' => $pluginSlug];
        $sut = new EmbeddedWP(make_container(), $config);
        $do_action = Test::replace('do_action');

        $sut->activatePlugins();

        $do_action->wasCalledWithOnce(["activate_{$pluginSlug}"]);
    }

    public function weirdPluginBasenames()
    {
        return [
            ['some-file.js'],
            ['some-file'],
            ['some-folder/some-file'],
            ['some-folder/some-file.js'],
            ['rails.rb']
        ];
    }

    /**
     * @test
     * it should throw for weird plugin basenames in plugins to activate
     * @dataProvider weirdPluginBasenames
     */
    public function it_should_throw_for_weird_plugin_basenames_in_plugins_to_activate($weirdPluginBasenamej)
    {
        $config = ['activatePlugins' => $weirdPluginBasenamej];
        $sut = new EmbeddedWP(make_container(), $config);
        $this->expectConfigException();

        $sut->activatePlugins();
    }

    private function expectConfigException()
    {
        $this->setExpectedException('Codeception\Exception\ModuleConfigException');
    }

    /**
     * @test
     * it should cast main plugin file to folder and plugin file format
     */
    public function it_should_cast_main_plugin_file_to_folder_and_plugin_file_format()
    {
        $projectRoot = __DIR__;
        $pathFinder = Test::replace('tad\EmbeddedWp\PathFinder')->method('getRootDir', $projectRoot)->get();
        $config = [
            'activatePlugins' => 'some-plugin.php',
            'mainFile' => 'some-plugin.php'
        ];
        $sut = new EmbeddedWP(make_container(), $config, $pathFinder);
        $do_action = Test::replace('do_action');

        $sut->activatePlugins();

        $do_action->wasCalledWithOnce(['activate_' . basename(__DIR__) . '/some-plugin.php']);
    }

    /**
     * @test
     * it should throw if required plugin does not exist
     */
    public function it_should_throw_if_required_plugin_does_not_exist()
    {
        $config = ['requiredPlugins' => 'some-plugin.php'];
        $sut = new EmbeddedWP(make_container(), $config);
        $this->expectConfigException();

        $sut->loadRequiredPlugins();
    }

    /**
     * @test
     * it should allow for required plugin path to be relative to project root
     */
    public function it_should_allow_for_required_plugin_path_to_be_relative_to_project_root()
    {
        $projectRoot = VfsStream::url('folder_tree') . '/my-plugin';
        $embeddedWpPath = $projectRoot . '/vendor/lucatume/wp-embedded/src/embedded-wordpress';
        $pathFinder = new Paths($projectRoot, $embeddedWpPath);
        $filesystem = Test::replace('Symfony\Component\Filesystem\Filesystem')->method('symlink')->get();
        $pluginRelativePath = 'vendor/required-plugins/plugin-b/plugin-b.php';
        $sut = new EmbeddedWP(make_container(), ['requiredPlugins' => $pluginRelativePath], $pathFinder, $filesystem);

        $sut->loadRequiredPlugins();

        $from = dirname($projectRoot . '/' . $pluginRelativePath);
        $destination = $embeddedWpPath . '/wp-content/plugins/plugin-b';
        $filesystem->wasCalledWithOnce([$from, $destination], 'symlink');
    }

    /**
     * @test
     * it should allow for required plugins to be specified as abspath
     */
    public function it_should_allow_for_required_plugins_to_be_specified_as_abspath()
    {
        $rootFolder = VfsStream::url('folder_tree');
        $pluginABasename = 'plugin-a/plugin-a.php';
        $clonedPluginPath = $rootFolder . '/Users/Me/cloned-plugins/' . $pluginABasename;
        $config = ['requiredPlugins' => $clonedPluginPath];
        $embeddedWpPath = $rootFolder . '/my-plugin/vendor/lucatume/wp-embedded/src/embedded-wordpress';
        $pathFinder = new Paths($rootFolder, $embeddedWpPath);
        $filesystem = Test::replace('Symfony\Component\Filesystem\Filesystem')->method('symlink')->get();
        $sut = new EmbeddedWP(make_container(), $config, $pathFinder, $filesystem);

        $sut->loadRequiredPlugins();

        $destination = dirname($embeddedWpPath . '/wp-content/plugins/' . $pluginABasename);
        $from = dirname($clonedPluginPath);
        $filesystem->wasCalledWithOnce([
            $from,
            $destination
        ], 'symlink');
    }

    /**
     * @test
     * it should throw if required plugin abspath points to folder and not file
     */
    public function it_should_throw_if_required_plugin_abspath_points_to_folder_and_not_file()
    {
        $projectRoot = VfsStream::url('folder_tree');
        $clonedPluginPath = VfsStream::url('folder_tree') . '/Users/Me/cloned-plugins/plugin-a';
        $embeddedWpPath = $projectRoot . '/vendor/lucatume/wp-embedded/src/embedded-wordpress';
        $config = ['requiredPlugins' => $clonedPluginPath];
        $pathFinder = (new Paths())->setWPRootFolder($embeddedWpPath);
        $sut = new EmbeddedWP(make_container(), $config, $pathFinder);

        $this->expectConfigException();

        $sut->loadRequiredPlugins();
    }

    /**
     * @test
     * it should throw if relative path required plugin is not pointing to file
     */
    public function it_should_throw_if_relative_path_required_plugin_is_not_pointing_to_file()
    {
        $projectRoot = VfsStream::url('folder_tree') . '/my-plugin';
        $embeddedWpPath = $projectRoot . '/vendor/lucatume/wp-embedded/src/embedded-wordpress';
        $pathFinder = new Paths($projectRoot, $embeddedWpPath);
        $filesystem = Test::replace('Symfony\Component\Filesystem\Filesystem')->method('symlink')->get();
        $pluginRelativePath = 'vendor/required-plugins/plugin-b';
        $sut = new EmbeddedWP(make_container(), ['requiredPlugins' => $pluginRelativePath], $pathFinder, $filesystem);

        $this->expectConfigException();

        $sut->loadRequiredPlugins();
    }

    protected function _before()
    {
        Test::setUp();
        $structure = [
            'Users' => [
                'Me' => [
                    'cloned-plugins' => [
                        'plugin-a' => [
                            'plugin-a.php' => '<?php //plugin-a'
                        ]
                    ]
                ]
            ],
            'my-plugin' => [
                'vendor' => [
                    'required-plugins' => ['plugin-b' => ['plugin-b.php' => '<?php // plugin-b']]
                ],
                'lucatume' => [
                    'wp-embedded' => [
                        'src' => [
                            'embedded-wordpress' => [
                                // EmbeddedWp will look up this file to make sure this is a valid WP install
                                'wp-settings.php' => '<?php // wp-settings.php'
                            ]
                        ]
                    ]
                ]
            ]
        ];
        VfsStream::setup('folder_tree', null, $structure);
    }

    protected function _after()
    {
        Test::tearDown();
    }
}


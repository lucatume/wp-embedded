<?php
namespace Codeception\Module;


use org\bovigo\vfs\vfsStream;
use tad\FunctionMocker\FunctionMocker as Test;

class EmbeddedWPTest extends \Codeception\TestCase\Test
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        Test::setUp();
        $project_dir = VfsStream::setup('project_dir', null, [
            'plugins' => ['some-plugin' => ['some-file.php' => 'Plugin main file']],
            'includes' => ['embedded-wordpress' => []]
        ]);
        VfsStream::copyFromFileSystem(dirname(dirname(dirname(dirname(__DIR__)))) . '/src/embedded-wordpress', $project_dir);
    }

    protected function _after()
    {
        Test::tearDown();
    }

    /**
     * @test
     * it should do activation action if no plugins to activate
     */
    public function it_should_do_activation_action_if_no_plugins_to_activate()
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
        $plugins = ['plugin-a/plugin-a.php',
            'plugin-b/plugin-b.php',
            'plugin-c/plugin-c.php'];
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

    /**
     * @test
     * it should cast main plugin file to folder and plugin file format
     */
    public function it_should_cast_main_plugin_file_to_folder_and_plugin_file_format()
    {
        $projectRoot = __DIR__;
        $pathFinder = Test::replace('tad\EmbeddedWp\PathFinder')->method('getRootDir', $projectRoot)->get();
        $config = ['activatePlugins' => 'some-plugin.php',
            'mainFile' => 'some-plugin.php'];
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
        $projectRoot = VfsStream::url('project_dir');
        $pathFinder = Test::replace('tad\EmbeddedWP\PathFinder')
            ->method('getRootDir', $projectRoot)
            ->method('getWpRootFolder', $projectRoot . '/includes/embedded-wordpress')
            ->get();
        $filesystem = Test::replace('Symfony\Component\Filesystem\Filesystem')->method('symlink')->get();
        $sut = new EmbeddedWP(make_container(), ['requiredPlugins' => 'plugins/some-plugin/some-file.php'], $pathFinder, $filesystem);

        $sut->loadRequiredPlugins();

        $filesystem->wasCalledOnce('symlink');
    }

    private function expectConfigException()
    {
        $this->setExpectedException('Codeception\Exception\ModuleConfigException');
    }
}


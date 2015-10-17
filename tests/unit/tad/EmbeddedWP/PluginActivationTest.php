<?php
namespace tad\EmbeddedWP;

use org\bovigo\vfs\vfsStream;
use tad\EmbeddedWP\Filesystem\Paths;
use tad\FunctionMocker\FunctionMocker as Test;

class PluginActivationTest extends \Codeception\TestCase\Test
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @test
     * it should throw if plugin to activate is not a string
     */
    public function it_should_throw_if_plugin_is_not_a_string()
    {
        $config = [];
        $plugin = 23;
        $pathFinder = $this->makeFinder();

        $this->setExpectedException('Codeception\Exception\ModuleConfigException');

        new PluginActivation($plugin, $config, $pathFinder);
    }

    /**
     * @return Paths
     */
    protected function makeFinder()
    {
        $pathFinder = new Paths(__DIR__, __DIR__);
        return $pathFinder;
    }

    /**
     * @test
     * it should cast main plugin file to folder file format
     */
    public function it_should_cast_main_plugin_file_to_folder_file_format()
    {
        $config = ['mainFile' => 'main.php'];
        $plugin = 'main.php';
        $pathFinder = $this->makeFinder();
        $do_action = Test::replace('do_action');

        $sut = new PluginActivation($plugin, $config, $pathFinder);
        $sut->activate();

        $do_action->wasCalledWithOnce(['activate_' . basename(__DIR__) . '/main.php']);
    }

    /**
     * @test
     * it should activate single file plugins
     */
    public function it_should_activate_single_file_plugins()
    {
        $config = ['mainFile' => 'main.php'];
        $plugin = 'another.php';
        $pathFinder = $this->makeFinder();
        $do_action = Test::replace('do_action');

        $sut = new PluginActivation($plugin, $config, $pathFinder);
        $sut->activate();

        $do_action->wasCalledWithOnce(['activate_' . 'another.php']);
    }

    /**
     * @test
     * it should activate folder and file plugins
     */
    public function it_should_activate_folder_and_file_plugins()
    {
        $config = ['mainFile' => 'main.php'];
        $plugin = 'folder/file.php';
        $pathFinder = $this->makeFinder();
        $do_action = Test::replace('do_action');

        $sut = new PluginActivation($plugin, $config, $pathFinder);
        $sut->activate();

        $do_action->wasCalledWithOnce(['activate_' . 'folder/file.php']);
    }

    /**
     * @test
     * it should allow for plugins to be passed as abspaths
     */
    public function it_should_allow_for_plugins_to_be_passed_as_abspaths()
    {
        $id = md5(time());
        vfsStream::setup($id, null, [
            'Users' => ['Me' => ['plugin' => ['plugin.php' => '<?php //contents']]]
        ]);
        $config = ['mainFile' => 'main.php'];
        $plugin = vfsStream::url($id . '/Users/Me/plugin/plugin.php');
        $pathFinder = $this->makeFinder();
        $do_action = Test::replace('do_action');

        $sut = new PluginActivation($plugin, $config, $pathFinder);
        $sut->activate();

        $do_action->wasCalledWithOnce(['activate_' . 'plugin/plugin.php']);
    }

    /**
     * @test
     * it should throw if plugin has wrong pattern
     */
    public function it_should_throw_if_plugin_has_wrong_pattern()
    {
        $plugin = 'some/path/some/file.php';
        $config = ['mainFile' => 'main.php'];
        $pathFinder = $this->makeFinder();

        $this->setExpectedException('Codeception\Exception\ModuleConfigException');

        new PluginActivation($plugin, $config, $pathFinder);
    }

    protected function _before()
    {
    }

    protected function _after()
    {
    }
}
<?php
namespace tad\EmbeddedWp;

use org\bovigo\vfs\vfsStream;
use tad\EmbeddedWP\Filesystem\Paths;
use tad\EmbeddedWP\Plugin\MainPluginLoader;
use tad\EmbeddedWp\Tests\EmbeddedWPTest;
use tad\FunctionMocker\FunctionMocker as Test;

class MainPluginLoaderTest extends EmbeddedWPTest
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    protected $mainPluginFilePath;
    protected $filesystem;
    protected $pathFinder;
    protected $embeddedWpPath;
    protected $rootDirName = 'MainPluginLoaderTest';

    public function notStrings()
    {
        return [
            [23],
            [array()],
            [array('foo')],
            [new \stdClass()],
            [-1]
        ];
    }

    /**
     * @test
     * it should throw if main plugin is not a string
     * @dataProvider notStrings
     */
    public function it_should_throw_if_main_plugin_is_not_a_string($notAString)
    {
        $this->setExpectedException('Codeception\Exception\ModuleConfigException');

        new MainPluginLoader($notAString, new Paths(), new \tad\WPBrowser\Filesystem\Filesystem());
    }

    /**
     * @test
     * it should throw if main plugin does not exist
     */
    public function it_should_throw_if_main_plugin_does_not_exist()
    {
        $this->setExpectedException('Codeception\Exception\ModuleConfigException');

        $this->getSut('/some/path/plugin/plugin.php');
    }

    /**
     * @return MainPluginLoader
     */
    protected function getSut($mainFile = null)
    {
        $mainFile = $mainFile ?: $this->mainPluginFilePath;
        $sut = new MainPluginLoader($mainFile, $this->pathFinder, $this->filesystem);
        return $sut;
    }

    /**
     * @test
     * it should require the main plugin file
     */
    public function it_should_require_the_main_plugin_file()
    {
        $sut = new MainPluginLoader($this->mainPluginFilePath, $this->pathFinder, $this->filesystem);

        $sut->requireIt();

        $this->filesystem->wasCalledWithOnce([$this->mainPluginFilePath], 'requireOnce');
    }

    /**
     * @test
     * it should symlink the main plugin file
     */
    public function it_should_symlink_the_main_plugin_file()
    {
        $sut = $this->getSut();

        $sut->symlinkIt();

        $from = dirname($this->mainPluginFilePath);
        $to = $this->embeddedWpPath . '/wp-content/plugins/my-plugin';
        $this->filesystem->wasCalledWithOnce([$from, $to], 'symlink');
    }

    protected function _before()
    {
        parent::_before();
        $projectRoot = VfsStream::url($this->rootDirName) . '/my-plugin';
        $this->mainPluginFilePath = $projectRoot . '/my-plugin.php';
        $this->filesystem = Test::replace('tad\WPBrowser\Filesystem')->method('symlink')->method('requireOnce')->get();
        $this->embeddedWpPath = $projectRoot . '/vendor/lucatume/wp-embedded/src/embedded-wordpress';
        $this->pathFinder = new Paths($projectRoot, $this->embeddedWpPath);
    }
}
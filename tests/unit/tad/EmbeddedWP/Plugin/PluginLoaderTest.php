<?php
namespace tad\EmbeddedWP\Plugin;

use org\bovigo\vfs\vfsStream;
use tad\EmbeddedWP\Filesystem\Paths;
use tad\FunctionMocker\FunctionMocker as Test;

class PluginLoaderTest extends \Codeception\TestCase\Test
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @test
     * it should throw if plugin is not string
     */
    public function it_should_throw_if_plugin_is_not_string()
    {
        $plugin = 23;
        $pathFinder = new Paths(__DIR__, __DIR__);
        $filesystem = Test::replace('tad\WPBrowser\Filesystem\Filesystem')->method('requireOnce')->method('symlink')->get();

        $this->setExpectedException('Codeception\Exception\ModuleConfigException');

        new PluginLoader($plugin, $pathFinder, $filesystem);
    }

    /**
     * @test
     * it should throw if plugin is not existing relative or abspath
     */
    public function it_should_throw_if_plugin_is_not_existing_relative_or_abspath()
    {
        $plugin = 'foo/baz/bar.php';
        $pathFinder = new Paths(__DIR__, __DIR__);
        $filesystem = Test::replace('tad\WPBrowser\Filesystem\Filesystem')->method('requireOnce')->method('symlink')->get();

        $this->setExpectedException('Codeception\Exception\ModuleConfigException');

        new PluginLoader($plugin, $pathFinder, $filesystem);
    }

    /**
     * @test
     * it should require abspath plugins
     */
    public function it_should_require_abspath_plugins()
    {
        $id = md5(time());
        vfsStream::setup($id, null, [
            'Users' => ['Me' => ['plugin' => ['plugin.php' => '<?php // content']]]
        ]);
        $plugin = vfsStream::url($id . '/Users/Me/plugin/plugin.php');
        $this->assertFileExists($plugin);
        $pathFinder = new Paths(__DIR__, __DIR__);
        $filesystem = Test::replace('tad\WPBrowser\Filesystem\Filesystem')->method('requireOnce')->method('symlink')->get();

        $sut = new PluginLoader($plugin, $pathFinder, $filesystem);

        $sut->requireIt();

        $filesystem->wasCalledWithOnce([$plugin], 'requireOnce');
    }

    /**
     * @test
     * it should require relative path plugins
     */
    public function it_should_require_relative_path_plugins()
    {
        $id = md5(time());
        vfsStream::setup($id, null, [
            'Users' => ['Me' => ['plugin' => ['plugin.php' => '<?php //content']]],
            'root' => ['vendor' => ['plugin_author' => ['plugin' => ['plugin.php' => '<?php //content']]]]
        ]);
        $plugin = 'vendor/plugin_author/plugin/plugin.php';
        $pathFinder = new Paths(vfsStream::url($id . '/root'), __DIR__);
        $filesystem = Test::replace('tad\WPBrowser\Filesystem\Filesystem')->method('requireOnce')->method('symlink')->get();

        $sut = new PluginLoader($plugin, $pathFinder, $filesystem);

        $sut->requireIt();

        $filesystem->wasCalledWithOnce([vfsStream::url($id . '/root/vendor/plugin_author/plugin/plugin.php')], 'requireOnce');
    }

    /**
     * @test
     * it should symlink absolute path plugins
     */
    public function it_should_symlink_absolute_path_plugins()
    {
        $id = md5(time());
        vfsStream::setup($id, null, [
            'Users' => ['Me' => ['plugin' => ['plugin.php' => '<?php //content']]]
        ]);
        $plugin = vfsStream::url($id . '/Users/Me/plugin/plugin.php');
        $pathFinder = new Paths(__DIR__, __DIR__);
        $filesystem = Test::replace('tad\WPBrowser\Filesystem\Filesystem')->method('requireOnce')->method('symlink')->get();

        $sut = new PluginLoader($plugin, $pathFinder, $filesystem);

        $sut->symlinkIt();

        $to = __DIR__ . '/wp-content/plugins/plugin';
        $filesystem->wasCalledWithOnce([dirname($plugin), $to], 'symlink');
    }

    /**
     * @test
     * it should symlink relative paths plugins
     */
    public function it_should_symlink_relative_paths_plugins()
    {
        $id = md5(time());
        vfsStream::setup($id, null, [
            'Users' => ['Me' => ['plugin' => ['plugin.php' => '<?php //content']]],
            'root' => ['vendor' => ['plugin_author' => ['plugin' => ['plugin.php' => '<?php //content']]]]
        ]);
        $plugin = 'vendor/plugin_author/plugin/plugin.php';
        $pathFinder = new Paths(vfsStream::url($id . '/root'), __DIR__);
        $filesystem = Test::replace('tad\WPBrowser\Filesystem\Filesystem')->method('requireOnce')->method('symlink')->get();

        $sut = new PluginLoader($plugin, $pathFinder, $filesystem);

        $sut->symlinkIt();

        $from = vfsStream::url($id . '/root/vendor/plugin_author/plugin');
        $to = __DIR__ . '/wp-content/plugins/plugin';
        $filesystem->wasCalledWithOnce([$from, $to], 'symlink');
    }

    /**
     * @test
     * it should not symlink plugin if existing already
     */
    public function it_should_not_symlink_plugin_if_existing_already()
    {
        $id = md5(time());
        vfsStream::setup($id, null, [
            'Users' => ['Me' => ['plugin' => ['plugin.php' => '<?php //content']]],
            'root' => ['vendor' => ['plugin_author' => ['plugin' => ['plugin.php' => '<?php //content']]]],
            'embedded-wp' => ['wp-content' => ['plugins' => ['plugin' => ['plugin.php' => '<?php //content']]]]
        ]);
        $plugin = vfsStream::url($id . '/root/vendor/plugin_author/plugin/plugin.php');
        $this->assertFileExists($plugin);
        $pathFinder = new Paths(vfsStream::url($id . '/root'), vfsStream::url($id . '/embedded-wp'));
        $filesystem = Test::replace('tad\WPBrowser\Filesystem\Filesystem')->method('requireOnce')->method('symlink')->get();

        $sut = new PluginLoader($plugin, $pathFinder, $filesystem);

        $sut->symlinkIt();

        $filesystem->wasNotCalled('symlink');
    }

    protected function _before()
    {
    }

    protected function _after()
    {
    }
}
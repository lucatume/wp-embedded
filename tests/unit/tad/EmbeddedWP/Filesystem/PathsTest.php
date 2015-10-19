<?php
namespace tad\EmbeddedWP\Filesystem;


class PathsTest extends \Codeception\TestCase\Test
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @test
     * it should return the embedded WP root folder as root
     */
    public function it_should_return_the_embedded_wp_root_folder_as_root()
    {
        $sut = new Paths();
        $this->assertEquals($this->getRootFolder(), $sut->getWpRootFolder());
    }

    private function getRootFolder($frag = null)
    {
        return codecept_root_dir('embedded-wordpress' . $frag);
    }

    /**
     * @test
     * it should return the wp contents folder
     */
    public function it_should_return_the_wp_contents_folder()
    {
        $sut = new Paths();
        $this->assertEquals($this->getRootFolder('/wp-content'), $sut->getWPContentFolder());
    }

    /**
     * @test
     * it should return wp themes folder
     */
    public function it_should_return_wp_themes_folder()
    {
        $sut = new Paths();
        $this->assertEquals($this->getRootFolder('/wp-content/themes'), $sut->getWPThemesFolder());
    }

    /**
     * @test
     * it should return wp mu-plugins folder
     */
    public function it_should_return_wp_mu_plugins_folder()
    {
        $sut = new Paths();
        $this->assertEquals($this->getRootFolder('/wp-content/mu-plugins'), $sut->getWPMuPluginsFolder());
    }

    /**
     * @test
     * it should return wp plugins folder
     */
    public function it_should_return_wp_plugins_folder()
    {
        $sut = new Paths();
        $this->assertEquals($this->getRootFolder('/wp-content/plugins'), $sut->getWpPluginsFolder());
    }

    protected function _before()
    {
    }

}
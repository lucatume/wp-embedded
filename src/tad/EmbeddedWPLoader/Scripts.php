<?php
namespace tad\EmbeddedWpLoader;

use Composer\Script\Event;

class Scripts
{
    // http://stackoverflow.com/a/7288067, thanks!
    protected static function rmdirRecursive($dir)
    {
        $it = new \RecursiveDirectoryIterator($dir);
        $it = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $file) {
            if ('.' === $file->getBasename() || '..' === $file->getBasename()) continue;
            if ($file->isDir()) rmdir($file->getPathname());
            else unlink($file->getPathname());
        }
        rmdir($dir);
    }

    protected static function cleanDir(array $contents, $leave, $fromDir)
    {
        $_contents = array_filter($contents, function ($element) {
            return $element !== '.' && $element !== '..';
        });
        foreach ($_contents as $element) {
            if (!in_array($element, $leave)) {
                $element = $fromDir . DIRECTORY_SEPARATOR . $element;
                if (is_dir($element)) {
                    self::rmdirRecursive($element);
                } else {
                    unlink($element);
                }
            }
        }
    }

    protected static function recurseCopy($src, $dst)
    {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    self::recurseCopy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    public static function onPostUpdate(Event $event)
    {
        $config = $event->getComposer()->getConfig();
        $vendorDir = $config->get('vendor-dir');
        $embeddedWordpressFolder = $vendorDir . '/embedded-wordpress';

        if (!file_exists($embeddedWordpressFolder)) {
            return;
        }
        $baseDir = dirname(dirname(dirname(__FILE__)));
        $embeddedWordpressDestination = $baseDir . '/embedded-wordpress';
        $wpContentFolder = $embeddedWordpressFolder . '/wp-content';
        // move SQLite integration drop-in in wp-content folder
        self::copyDropIn($wpContentFolder);

        // remove default plugins
        self::cleanPlugins($wpContentFolder);

        // remove default themes
        self::cleanThemes($wpContentFolder);

        // move the embedded wordpress folder into source
        self::moveEmbeddedWordpress($embeddedWordpressFolder, $embeddedWordpressDestination);
    }

    /**
     * @param $wpContentFolder
     */
    protected static function copyDropIn($wpContentFolder)
    {
        $destination = $wpContentFolder . '/db.php';
        if (!file_exists($destination)) {
            copy($wpContentFolder . '/plugins/sqlite-integration/db.php', $destination);
        }
    }

    /**
     * @param $wpContentFolder
     * @return array
     */
    protected static function cleanPlugins($wpContentFolder)
    {
        $pluginsDir = $wpContentFolder . '/plugins';
        $plugins = scandir($pluginsDir);
        $leave = array('sqlite-integration', 'index.php');
        self::cleanDir($plugins, $leave, $pluginsDir);
        return $leave;
    }

    /**
     * @param $wpContentFolder
     */
    protected static function cleanThemes($wpContentFolder)
    {
        $themesDir = $wpContentFolder . '/themes';
        $themes = scandir($themesDir);
        $leave = array('twentyfifteen', 'index.php');
        self::cleanDir($themes, $leave, $themesDir);
    }

    protected static function moveEmbeddedWordpress($embeddedWordpressFolder, $embeddedWordpressDestination)
    {
        self::recurseCopy($embeddedWordpressFolder, $embeddedWordpressDestination);
        self::rmdirRecursive($embeddedWordpressFolder);
    }
}


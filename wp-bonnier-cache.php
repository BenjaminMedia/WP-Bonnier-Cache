<?php
/**
 * Plugin Name: WP Bonnier Cache
 * Plugin URI: http://bonnierpublications.com
 * Description: Bonnier Cache Plugin
 * Version: 1.0.0
 * Author: Magnus Flor
 * Author URI: http://bonnierpublications.com.
 */

namespace Bonnier\WP\Cache;

use Bonnier\WP\Cache\Models\Post;
use Bonnier\WP\Cache\Services\CacheApi;
use Bonnier\WP\Cache\Settings\SettingsPage;

defined('ABSPATH') or die('No script kiddies please!');

spl_autoload_register(function ($className) {
    if (strpos($className, __NAMESPACE__) !== false) {
        $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
        require_once __DIR__.DIRECTORY_SEPARATOR.WpBonnierCache::CLASS_DIR.DIRECTORY_SEPARATOR.$className.'.php';
    }
});

require_once __DIR__.'/includes/vendor/autoload.php';

class WpBonnierCache
{
    const TEXT_DOMAIN = 'wp-bonnier-cache';

    const CLASS_DIR = 'src';

    private static $instance;

    public $settings;

    public $file;

    public $basename;

    public $plugin_dir;

    public $plugin_url;

    private function __construct()
    {
        $this->file = __FILE__;
        $this->basename = plugin_basename($this->file);
        $this->plugin_dir = plugin_dir_path($this->file);
        $this->plugin_url = plugin_dir_url($this->file);

        load_plugin_textdomain(self::TEXT_DOMAIN, false, dirname($this->basename.'/languages'));

        $this->settings = new SettingsPage();
    }

    private function bootstrap()
    {
        Post::watch_post_changes($this->settings);
        CacheApi::bootstrap($this->settings);
    }

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
            global $wp_bonnier_cache;
            $wp_bonnier_cache = self::$instance;
            self::$instance->bootstrap();

            do_action('wp_bonnier_cache_loaded');
        }

        return self::$instance;
    }
}

function instance()
{
    return WpBonnierCache::instance();
}

add_action('plugins_loaded', __NAMESPACE__.'\instance', 0);

<?php
/**
 * Plugin Name: WP Bonnier Cache
 * Plugin URI: http://bonnierpublications.com
 * Description: Bonnier Cache Plugin
 * Version: 5.0.7
 * Author: Magnus Flor
 * Author URI: http://bonnierpublications.com
 */

defined('ABSPATH') or die('No script kiddies please!');

function loadBonnierCache()
{
    return \Bonnier\WP\Cache\WpBonnierCache::instance();
}

add_action('plugins_loaded', 'loadBonnierCache', 0);

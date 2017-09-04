<?php

namespace Bonnier\WP\Cache\Models;

use Bonnier\WP\Cache\Services\CacheApi;
use Bonnier\WP\Cache\Settings\SettingsPage;

class Post
{
    private static $settings;

    public static function watch_post_changes(SettingsPage $settingsPage)
    {
        self::$settings = $settingsPage;

        add_action('save_post', [__CLASS__, 'update_post']);
        add_action('delete_post', [__CLASS__, 'delete_post']);
    }

    public static function update_post($postId)
    {
        return CacheApi::update($postId);
    }

    public static function delete_post($postId)
    {
        return CacheApi::update($postId, true);
    }

    public static function is_published($postId)
    {
        return get_post_status($postId) === 'publish';
    }
}

<?php

namespace Bonnier\WP\Cache\Models;

use Bonnier\Willow\Base\Models\WpComposite;
use Bonnier\Willow\MuPlugins\Helpers\LanguageProvider;
use Bonnier\WP\Cache\Services\CacheApi;
use WP_Post;

class Post
{
    const POST_TYPES = ['review', 'contenthub_composite', 'post', 'page'];

    public static function watchPostChanges()
    {
        // triggers when post status changes to trash
        add_action('wp_trash_post', [__CLASS__, 'removePost'], 10, 1);
        add_action('publish_to_draft', [__CLASS__, 'removePost'], 10, 1);

        // publish post
        if (defined('SLUG_CHANGE_HOOK')) {
            // Only trigger if ContentHub plugin is added
            add_action(WpComposite::SLUG_CHANGE_HOOK, [__CLASS__, 'urlChanged'], 10, 3);
        }
        add_action('publish_to_publish', [__CLASS__, 'updatePost'], 10, 1);

        add_action('draft_to_publish', [__CLASS__, 'publishPost'], 10, 1);
        add_action('untrashed_post', [__CLASS__, 'publishPost'], 10, 1);
    }

    public static function publishPost($publishedPostID)
    {
        $publishedPost = get_post($publishedPostID);

        if (!in_array($publishedPost->post_type, static::POST_TYPES) || $publishedPost->post_status !== 'publish') {
            return;
        }

        CacheApi::add($publishedPost->ID);
    }

    public static function updatePost($postId)
    {
        $post = get_post($postId);
        if (!in_array($post->post_type, static::POST_TYPES) || $post->post_status !== 'publish') {
            return;
        }

        CacheApi::post(CacheApi::CACHE_UPDATE, get_permalink()); // Remove old url
        CacheApi::post(CacheApi::CACHE_UPDATE, get_permalink($postId)); // Add new

        if ($postId instanceof WP_Post) {
            $postId = $postId->ID;
        }

        $currentPostLang = LanguageProvider::getPostLanguage($postId);
        $translations = LanguageProvider::getPostTranslations($postId);

        // Unset the current one, due to we just updated it above
        unset($translations[$currentPostLang]);

        // Call update on all other articles
        foreach ($translations as $translation) {
            CacheApi::post(CacheApi::CACHE_UPDATE, get_permalink($translation)); // Add new
        }
    }

    public static function urlChanged($changedPostID, $oldLink, $newLink)
    {
        $changedPost = get_post($changedPostID);

        if (!in_array($changedPost->post_type, static::POST_TYPES) || $changedPost->post_status !== 'publish') {
            return;
        }

        CacheApi::post(CacheApi::CACHE_UPDATE, $oldLink); // Remove old url
        CacheApi::post(CacheApi::CACHE_UPDATE, $newLink); // Add new
    }

    public static function removePost($postID)
    {
        $changedPost = get_post($postID);

        if (!in_array($changedPost->post_type, static::POST_TYPES)) {
            return;
        }

        // if the post we try to trash is current published,

        CacheApi::update($postID);
    }

    /**
     * @param $postId
     * @return bool|void
     * @deprecated
     */
    public static function deletePost($postId)
    {
        CacheApi::update($postId);
    }

    public static function isPublished($postId)
    {
        return get_post_status($postId) === 'publish';
    }
}

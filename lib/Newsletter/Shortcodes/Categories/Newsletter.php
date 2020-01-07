<?php

namespace MailPoet\Newsletter\Shortcodes\Categories;

use MailPoet\Models\Newsletter as NewsletterModel;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Posts as WPPosts;

class Newsletter {
  public static function process(
    $shortcodeDetails,
    $newsletter,
    $subscriber,
    $queue,
    $content
  ) {
    switch ($shortcodeDetails['action']) {
      case 'subject':
        return ($newsletter) ? $newsletter->subject : false;

      case 'total':
        return substr_count($content, 'data-post-id');

      case 'post_title':
        preg_match_all('/data-post-id="(\d+)"/ism', $content, $posts);
        $postIds = array_unique($posts[1]);
        $latestPost = (!empty($postIds)) ? self::getLatestWPPost($postIds) : false;
        return ($latestPost) ? $latestPost['post_title'] : false;

      case 'number':
        if ($newsletter->type !== NewsletterModel::TYPE_NOTIFICATION_HISTORY) return false;
        $sentNewsletters =
          NewsletterModel::where('parent_id', $newsletter->parentId)
            ->where('status', NewsletterModel::STATUS_SENT)
            ->count();
        return ++$sentNewsletters;

      default:
        return false;
    }
  }

  public static function ensureConsistentQueryType(\WP_Query $query) {
    // Queries with taxonomies are autodetected as 'is_archive=true' and 'is_home=false'
    // while queries without them end up being 'is_archive=false' and 'is_home=true'.
    // This is to fix that by always enforcing constistent behavior.
    $query->is_archive = true; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    $query->is_home = false; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
  }

  private static function getLatestWPPost($postIds) {
    // set low priority to execute 'ensureConstistentQueryType' before any other filter
    $filterPriority = defined('PHP_INT_MIN') ? constant('PHP_INT_MIN') : ~PHP_INT_MAX;
    WPFunctions::get()->addAction('pre_get_posts', [get_called_class(), 'ensureConsistentQueryType'], $filterPriority);
    $posts = new \WP_Query(
      [
        'post_type' => WPPosts::getTypes(),
        'post__in' => $postIds,
        'posts_per_page' => 1,
        'ignore_sticky_posts' => true,
        'orderby' => 'post_date',
        'order' => 'DESC',
      ]
    );
    WPFunctions::get()->removeAction('pre_get_posts', [get_called_class(), 'ensureConsistentQueryType'], $filterPriority);
    return (!empty($posts->posts[0])) ?
      $posts->posts[0]->to_array() :
      false;
  }
}

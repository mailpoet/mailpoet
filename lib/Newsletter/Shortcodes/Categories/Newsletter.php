<?php

namespace MailPoet\Newsletter\Shortcodes\Categories;

use MailPoet\Models\Newsletter as NewsletterModel;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Posts as WPPosts;

if (!defined('ABSPATH')) exit;

class Newsletter {
  static function process(
    $shortcode_details,
    $newsletter,
    $subscriber,
    $queue,
    $content
  ) {
    switch ($shortcode_details['action']) {
      case 'subject':
        return ($newsletter) ? $newsletter->subject : false;

      case 'total':
        return substr_count($content, 'data-post-id');

      case 'post_title':
        preg_match_all('/data-post-id="(\d+)"/ism', $content, $posts);
        $post_ids = array_unique($posts[1]);
        $latest_post = (!empty($post_ids)) ? self::getLatestWPPost($post_ids) : false;
        return ($latest_post) ? $latest_post['post_title'] : false;

      case 'number':
        if ($newsletter->type !== NewsletterModel::TYPE_NOTIFICATION_HISTORY) return false;
        $sent_newsletters =
          NewsletterModel::where('parent_id', $newsletter->parent_id)
            ->where('status', NewsletterModel::STATUS_SENT)
            ->count();
        return ++$sent_newsletters;

      default:
        return false;
    }
  }

  public static function ensureConsistentQueryType(\WP_Query $query) {
    // Queries with taxonomies are autodetected as 'is_archive=true' and 'is_home=false'
    // while queries without them end up being 'is_archive=false' and 'is_home=true'.
    // This is to fix that by always enforcing constistent behavior.
    $query->is_archive = true;
    $query->is_home = false;
  }

  private static function getLatestWPPost($post_ids) {
    // set low priority to execute 'ensureConstistentQueryType' before any other filter
    $filter_priority = defined('PHP_INT_MIN') ? constant('PHP_INT_MIN') : ~PHP_INT_MAX;
    WPFunctions::get()->addAction('pre_get_posts', [get_called_class(), 'ensureConsistentQueryType'], $filter_priority);
    $posts = new \WP_Query(
      [
        'post_type' => WPPosts::getTypes(),
        'post__in' => $post_ids,
        'posts_per_page' => 1,
        'ignore_sticky_posts' => true,
        'orderby' => 'post_date',
        'order' => 'DESC',
      ]
    );
    WPFunctions::get()->removeAction('pre_get_posts', [get_called_class(), 'ensureConsistentQueryType'], $filter_priority);
    return (!empty($posts->posts[0])) ?
      $posts->posts[0]->to_array() :
      false;
  }
}

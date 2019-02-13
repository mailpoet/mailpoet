<?php

namespace MailPoet\Newsletter\Shortcodes\Categories;

use MailPoet\Models\Newsletter as NewsletterModel;
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

  private static function getLatestWPPost($post_ids) {
    $posts = new \WP_Query(
      array(
        'post_type' => WPPosts::getTypes(),
        'post__in' => $post_ids,
        'posts_per_page' => 1,
        'ignore_sticky_posts' => true,
        'orderby' => 'post_date',
        'order' => 'DESC'
      )
    );
    return (!empty($posts->posts[0])) ?
      $posts->posts[0]->to_array() :
      false;
  }
}

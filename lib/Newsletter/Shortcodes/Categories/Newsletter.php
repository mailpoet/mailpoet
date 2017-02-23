<?php
namespace MailPoet\Newsletter\Shortcodes\Categories;

use MailPoet\Models\Newsletter as NewsletterModel;

if(!defined('ABSPATH')) exit;
require_once(ABSPATH . "wp-includes/pluggable.php");

class Newsletter {
  static function process($action,
    $action_argument,
    $action_argument_value,
    $newsletter,
    $subscriber,
    $queue,
    $content
  ) {
    switch($action) {
      case 'subject':
        return ($newsletter) ? $newsletter->subject : false;

      case 'total':
        return substr_count($content, 'data-post-id');

      case 'post_title':
        preg_match_all('/data-post-id="(\d+)"/ism', $content, $posts);
        $post_ids = array_unique($posts[1]);
        $latest_post = self::getLatestWPPost($post_ids);
        return ($latest_post) ? $latest_post['post_title'] : false;

      case 'number':
        if($newsletter->type !== NewsletterModel::TYPE_NOTIFICATION_HISTORY) return false;
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
<?php
if(!defined('ABSPATH')) exit;

require_once( ABSPATH . "wp-includes/pluggable.php" );

namespace MailPoet\Newsletter\Shortcodes\Categories;
use MailPoet\Models\SendingQueue;
use MailPoet\Newsletter\Shortcodes\ShortcodesHelper;

class Newsletter {
  static function process($action,
    $default_value = false,
    $newsletter,
    $subscriber,
    $queue = false,
    $content
  ) {
    switch($action) {
      case 'subject':
        return ($newsletter) ? $newsletter['subject'] : false;

      case 'total':
        return substr_count($content, 'data-post-id');

      case 'post_title':
        preg_match_all('/data-post-id="(\d+)"/ism', $content, $posts);
        $post_ids = array_unique($posts[1]);
        $latest_post = self::getLatestWPPost($post_ids);
        return ($latest_post) ? $latest_post['post_title'] : false;

      case 'number':
        if($newsletter['type'] !== 'notification') return false;
        $sent_newsletters =
          SendingQueue::where('newsletter_id', $newsletter['id'])
            ->where('status', 'completed')
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
    return (count($posts)) ?
      $posts->posts[0]->to_array() :
      false;
  }
}
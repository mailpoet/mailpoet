<?php
namespace MailPoet\Newsletter\Shortcodes\Categories;

use MailPoet\Models\SendingQueue;

require_once( ABSPATH . "wp-includes/pluggable.php" );

class Newsletter {
  /*
    {
      text: '<%= __('Newsletter Subject') %>',-
      shortcode: 'newsletter:subject',
    },
    {
      text: '<%= __('Total number of posts or pages') %>',
      shortcode: 'newsletter:total',
    },
    {
      text: '<%= __('Latest post title') %>',
      shortcode: 'newsletter:post_title',
    },
    {
      text: '<%= __('Issue number') %>',
      shortcode: 'newsletter:number',
    },
    {
      text: '<%= __('Issue number') %>',
      shortcode: 'newsletter:number',
    },
    {
      text: '<%= __('View in browser link') %>',
      shortcode: 'newsletter:view_in_browser',
    }
   */
  static function process($action,
    $default_value = false,
    $newsletter, $subscriber = false, $text) {
    if(is_object($newsletter)) {
      $newsletter = $newsletter->asArray();
    }
    switch($action) {
      case 'subject':
        return ($newsletter) ? $newsletter['subject'] : false;
      break;

      case 'total':
        return substr_count($text, 'data-post-id');
      break;

      case 'post_title':
        preg_match_all('/data-post-id="(\w+)"/ism', $text, $posts);
        $post_ids = array_unique($posts[1]);
        $latest_post = self::getLatestWPPost($post_ids);
        return ($latest_post) ? $latest_post['post_title'] : false;
      break;

      case 'number':
        if ($newsletter['type'] !== 'notification') return false;
        $sent_newsletters =
          SendingQueue::where('newsletter_id', $newsletter['id'])
            ->where('status', 'completed')
            ->count();
        return ++$sent_newsletters;
      break;

      case 'view_in_browser':
        return '<a href="#TODO">'.__('View in your browser').'</a>';
      break;

      case 'view_in_browser_url':
        return '#TODO';
      break;

      default:
        return false;
      break;
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
<?php
namespace MailPoet\Newsletter\Shortcodes\Categories;

use MailPoet\Models\SendingQueue;

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
        $post = wp_get_recent_posts(array('numberposts' => 1));
        return (isset($post[0])) ? $post[0]['post_title'] : false;
      break;

      case 'number':
        if ($newsletter['type'] !== 'notification') return false;
        $sent_newsletters = (int)
          SendingQueue::where('newsletter_id', $newsletter['id'])->count();
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
}
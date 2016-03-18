<?php
namespace MailPoet\Newsletter\Shortcodes\Categories;

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
  static function process($action, $default_value = false, $newsletter) {
    if(is_object($newsletter)) {
      $newsletter = $newsletter->asArray();
    }
    switch($action) {
      case 'subject':
        return ($newsletter) ? $newsletter['subject'] : false;
      break;

      case 'total':
        $posts = wp_count_posts();
        return $posts->publish;
      break;

      case 'post_title':
        $post = wp_get_recent_posts(array('numberposts' => 1));
        return (isset($post[0])) ? $post[0]['post_title'] : false;
      break;

      case 'number':
        // TODO: implement
        return 1;
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
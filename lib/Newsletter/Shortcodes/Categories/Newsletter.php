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
    }
   */
  static function process($action, $default_value = false, $newsletter) {
    if(is_object($newsletter)) {
      $newsletter = $newsletter->asArray();
    }
    switch($action) {
      case 'subject':
        return ($newsletter) ? $newsletter['subject'] : false;
      case 'total':
        $posts = wp_count_posts();
        return $posts->publish;
      case 'post_title':
        $post = wp_get_recent_posts(array('numberposts' => 1));
        return (isset($post[0])) ? $post[0]['post_title'] : false;
      case 'number':
        // TODO: implement
        return;
      default:
        return false;
    }
  }
}
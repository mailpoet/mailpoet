<?php
namespace MailPoet\Newsletter\Shortcodes;

class Shortcodes {
  public $newsletter;
  public $subscriber;
  public $queue;
  public $wp_user_preview;
  const SHORTCODE_CATEGORY_NAMESPACE = 'MailPoet\Newsletter\Shortcodes\Categories\\';

  function __construct(
    $newsletter = false,
    $subscriber = false,
    $queue = false,
    $wp_user_preview = false
  ) {
    $this->newsletter = $newsletter;
    $this->subscriber = $subscriber;
    $this->queue = $queue;
    $this->wp_user_preview = $wp_user_preview;
  }

  function extract($content, $categories = false) {
    $categories = (is_array($categories)) ? implode('|', $categories) : false;
    // match: [category:shortcode] or [category|category|...:shortcode]
    // dot not match: [category://shortcode] - avoids matching http/ftp links
    $regex = sprintf(
      '/\[%s:(?!\/\/).*?\]/ism',
      ($categories) ? '(?:' . $categories . ')' : '(?:\w+)'
    );
    preg_match_all($regex, $content, $shortcodes);
    $shortcodes = $shortcodes[0];
    return (count($shortcodes)) ?
      array_unique($shortcodes) :
      false;
  }

  function match($shortcode) {
    preg_match(
      '/\[(?P<category>\w+)?:(?P<action>\w+)(?:.*?\|.*?(?P<argument>\w+):(?P<argument_value>.*?))?\]/',
      $shortcode,
      $match
    );
    return $match;
  }

  function process($shortcodes, $content = false) {
    $_this = $this;
    $processed_shortcodes = array_map(
      function($shortcode) use ($content, $_this) {
        $shortcode_details = $_this->match($shortcode);
        $shortcode_category = !empty($shortcode_details['category']) ?
          ucfirst($shortcode_details['category']) :
          false;
        $shortcode_action = !empty($shortcode_details['action']) ?
          $shortcode_details['action'] :
          false;
        $shortcode_class =
          Shortcodes::SHORTCODE_CATEGORY_NAMESPACE . $shortcode_category;
        $shortcode_argument = !empty($shortcode_details['argument']) ?
          $shortcode_details['argument'] :
          false;
        $shortcode_argument_value = !empty($shortcode_details['argument_value']) ?
          $shortcode_details['argument_value'] :
          false;
        if(!class_exists($shortcode_class)) {
          $custom_shortcode = apply_filters(
            'mailpoet_newsletter_shortcode',
            $shortcode,
            $_this->newsletter,
            $_this->subscriber,
            $_this->queue,
            $content,
            $_this->wp_user_preview
          );
          return ($custom_shortcode === $shortcode) ?
            false :
            $custom_shortcode;
        }
        return $shortcode_class::process(
          $shortcode_action,
          $shortcode_argument,
          $shortcode_argument_value,
          $_this->newsletter,
          $_this->subscriber,
          $_this->queue,
          $content,
          $_this->wp_user_preview
        );
      }, $shortcodes);
    return $processed_shortcodes;
  }

  function replace($content, $categories = false) {
    $shortcodes = $this->extract($content, $categories);
    if(!$shortcodes) {
      return $content;
    }
    $processed_shortcodes = $this->process($shortcodes, $content);
    $shortcodes = array_intersect_key($shortcodes, $processed_shortcodes);
    return str_replace($shortcodes, $processed_shortcodes, $content);
  }
}
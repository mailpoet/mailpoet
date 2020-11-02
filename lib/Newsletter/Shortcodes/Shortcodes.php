<?php

namespace MailPoet\Newsletter\Shortcodes;

use MailPoet\WP\Functions as WPFunctions;

class Shortcodes {
  const SHORTCODE_CATEGORY_NAMESPACE = 'MailPoet\Newsletter\Shortcodes\Categories\\';
  public $newsletter;
  public $subscriber;
  public $queue;
  public $wpUserPreview;

  public function __construct(
    $newsletter = false,
    $subscriber = false,
    $queue = false,
    $wpUserPreview = false
  ) {
    $this->newsletter = $newsletter;
    $this->subscriber = $subscriber;
    $this->queue = $queue;
    $this->wpUserPreview = $wpUserPreview;
  }

  public function extract($content, $categories = false) {
    $categories = (is_array($categories)) ? implode('|', $categories) : false;
    // match: [category:shortcode] or [category|category|...:shortcode]
    // dot not match: [category://shortcode] - avoids matching http/ftp links
    $regex = sprintf(
      '/\[%s:(?!\/\/).*?\]/i',
      ($categories) ? '(?:' . $categories . ')' : '(?:\w+)'
    );
    preg_match_all($regex, $content, $shortcodes);
    $shortcodes = $shortcodes[0];
    return (count($shortcodes)) ?
      array_unique($shortcodes) :
      false;
  }

  public function match($shortcode) {
    preg_match(
      '/\[(?P<category>\w+)?:(?P<action>\w+)(?:.*?\|.*?(?P<argument>\w+):(?P<argument_value>.*?))?\]/',
      $shortcode,
      $match
    );
    return $match;
  }

  public function process($shortcodes, $content = false) {
    $_this = $this;
    $processedShortcodes = array_map(
      function($shortcode) use ($content, $_this) {
        $shortcodeDetails = $_this->match($shortcode);
        $shortcodeDetails['shortcode'] = $shortcode;
        $shortcodeDetails['category'] = !empty($shortcodeDetails['category']) ?
          $shortcodeDetails['category'] :
          false;
        $shortcodeDetails['action'] = !empty($shortcodeDetails['action']) ?
          $shortcodeDetails['action'] :
          false;
        $shortcodeDetails['action_argument'] = !empty($shortcodeDetails['argument']) ?
          $shortcodeDetails['argument'] :
          false;
        $shortcodeDetails['action_argument_value'] = !empty($shortcodeDetails['argument_value']) ?
          $shortcodeDetails['argument_value'] :
          false;
        $shortcodeClass =
          Shortcodes::SHORTCODE_CATEGORY_NAMESPACE . ucfirst($shortcodeDetails['category']);
        if (!class_exists($shortcodeClass)) {
          $customShortcode = WPFunctions::get()->applyFilters(
            'mailpoet_newsletter_shortcode',
            $shortcode,
            $_this->newsletter,
            $_this->subscriber,
            $_this->queue,
            $content,
            $_this->wpUserPreview
          );
          return ($customShortcode === $shortcode) ?
            false :
            $customShortcode;
        }
        return $shortcodeClass::process(
          $shortcodeDetails,
          $_this->newsletter,
          $_this->subscriber,
          $_this->queue,
          $content,
          $_this->wpUserPreview
        );
      }, $shortcodes);
    return $processedShortcodes;
  }

  public function replace($content, $contentSource = null, $categories = null) {
    $shortcodes = $this->extract($content, $categories);
    if (!$shortcodes) {
      return $content;
    }
    // if content contains only shortcodes (e.g., [newsletter:post_title]) but their processing
    // depends on some other content (e.g., "post_id" inside a rendered newsletter),
    // then we should use that content source when processing shortcodes
    $processedShortcodes = $this->process(
      $shortcodes,
      ($contentSource) ? $contentSource : $content
    );
    $shortcodes = array_intersect_key($shortcodes, $processedShortcodes);
    return str_replace($shortcodes, $processedShortcodes, $content);
  }
}

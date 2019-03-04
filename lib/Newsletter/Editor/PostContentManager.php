<?php

namespace MailPoet\Newsletter\Editor;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class PostContentManager {
  const WP_POST_CLASS = 'mailpoet_wp_post';

  public $max_excerpt_length = 60;


  function __construct() {
    $wp = new WPFunctions;
    $this->max_excerpt_length = $wp->applyFilters('mailpoet_newsletter_post_excerpt_length', $this->max_excerpt_length);
  }

  function getContent($post, $displayType) {
    if ($displayType === 'titleOnly') {
      return '';
    } elseif ($displayType === 'excerpt') {
      // get excerpt
      if (!empty($post->post_excerpt)) {
        return self::stripShortCodes($post->post_excerpt);
      } else {
        return $this->generateExcerpt(self::stripShortCodes($post->post_content));
      }
    } else {
      return self::stripShortCodes($post->post_content);
    }
  }

  function filterContent($content, $display_type, $with_post_class = true) {
    $content = self::convertEmbeddedContent($content);

    // convert h4 h5 h6 to h3
    $content = preg_replace('/<([\/])?h[456](.*?)>/', '<$1h3$2>', $content);

    // convert currency signs
    $content = str_replace(
      array('$', '€', '£', '¥'),
      array('&#36;', '&euro;', '&pound;', '&#165;'),
      $content
    );

    // strip useless tags
    $tags_not_being_stripped = array(
      '<p>', '<em>', '<span>', '<b>', '<strong>', '<i>',
      '<a>', '<ul>', '<ol>', '<li>', '<br>', '<blockquote>'
    );
    if ($display_type === 'full') {
      $tags_not_being_stripped =  array_merge($tags_not_being_stripped, array('<figure>', '<img>', '<h1>', '<h2>', '<h3>'));
    }

    $content = strip_tags($content, implode('', $tags_not_being_stripped));
    if ($with_post_class) {
      $content = str_replace('<p', '<p class="' . self::WP_POST_CLASS .'"', WPFunctions::get()->wpautop($content));
    } else {
      $content = WPFunctions::get()->wpautop($content);
    }
    $content = trim($content);

    return $content;
  }

  private function generateExcerpt($content) {
    // if excerpt is empty then try to find the "more" tag
    $excerpts = explode('<!--more-->', $content);
    if (count($excerpts) > 1) {
      // <!--more--> separator was present
      return $excerpts[0];
    } else {
      // Separator not present, try to shorten long posts
      return WPFunctions::get()->wpTrimWords($content, $this->max_excerpt_length, ' &hellip;');
    }
  }

  private function stripShortCodes($content) {
    // remove captions
    $content = preg_replace(
      "/\[caption.*?\](.*<\/a>)(.*?)\[\/caption\]/",
      '$1',
      $content
    );

    // remove other shortcodes
    $content = preg_replace('/\[[^\[\]]*\]/', '', $content);

    return $content;
  }

  private function convertEmbeddedContent($content = '') {
    // remove embedded video and replace with links
    $content = preg_replace(
      '#<iframe.*?src=\"(.+?)\".*><\/iframe>#',
      '<a href="$1">'.__('Click here to view media.', 'mailpoet').'</a>',
      $content
    );

    // replace youtube links
    $content = preg_replace(
      '#http://www.youtube.com/embed/([a-zA-Z0-9_-]*)#Ui',
      'http://www.youtube.com/watch?v=$1',
      $content
    );

    return $content;
  }
}

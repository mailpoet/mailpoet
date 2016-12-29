<?php
namespace MailPoet\Newsletter\Editor;

if(!defined('ABSPATH')) exit;

class PostContentManager {

  const MAX_EXCERPT_LENGTH = 60;
  const WP_POST_CLASS = 'mailpoet_wp_post';

  function getContent($post, $displayType) {
    if($displayType === 'titleOnly') {
      return '';
    } elseif($displayType === 'excerpt') {
      // get excerpt
      if(!empty($post->post_excerpt)) {
        return $post->post_excerpt;
      } else {
        return $this->generateExcerpt($post->post_content);
      }
    } else {
      return $post->post_content;
    }
  }

  function filterContent($content) {
    $content = self::stripShortCodes($content);
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
      '<img>', '<p>', '<em>', '<span>', '<b>', '<strong>', '<i>', '<h1>',
      '<h2>', '<h3>', '<a>', '<ul>', '<ol>', '<li>', '<br>'
    );
    $content = strip_tags($content, implode('', $tags_not_being_stripped));
    $content = str_replace('<p', '<p class="' . self::WP_POST_CLASS .'"', wpautop($content));

    return $content;
  }

  private function generateExcerpt($content) {
    // if excerpt is empty then try to find the "more" tag
    $excerpts = explode('<!--more-->', $content);
    if(count($excerpts) > 1) {
      // <!--more--> separator was present
      return $excerpts[0];
    } else {
      // Separator not present, try to shorten long posts
      return wp_trim_words($content, self::MAX_EXCERPT_LENGTH, ' &hellip;');
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
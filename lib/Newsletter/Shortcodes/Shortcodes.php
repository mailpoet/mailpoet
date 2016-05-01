<?php
namespace MailPoet\Newsletter\Shortcodes;

class Shortcodes {
  public $newsletter;
  public $subscriber;
  public $queue;

  function __construct(
    $newsletter = false,
    $subscriber = false,
    $queue = false
  ) {
    $this->newsletter = (is_object($newsletter)) ?
      $newsletter->asArray() :
      $newsletter;
    $this->subscriber = (is_object($subscriber)) ?
      $subscriber->asArray() :
      $subscriber;
    $this->queue = (is_object($queue)) ?
      $queue->asArray() :
      $queue;
  }

  function extract($content, $limit = false) {
    $limit = (is_array($limit)) ? implode('|', $limit) : false;
    $regex = sprintf(
      '/\[%s:.*?\]/ism',
      ($limit) ? '(?:' . $limit . ')' : '(?:\w+)'
    );
    preg_match_all($regex, $content, $shortcodes);
    return array_unique($shortcodes[0]);
  }

  function match($shortcode) {
    preg_match(
      '/\[(?P<type>\w+):(?P<action>\w+)(?:.*?default:(?P<default>.*?))?\]/',
      $shortcode,
      $match
    );
    return $match;
  }

  function process($shortcodes, $content) {
    $processed_shortcodes = array_map(
      function($shortcode) use($content) {
        $shortcode_details = $this->match($shortcode);
        $shortcode_type = ucfirst($shortcode_details['type']);
        $shortcode_action = $shortcode_details['action'];
        $shortcode_class =
          __NAMESPACE__ . '\\Categories\\' . $shortcode_type;
        $shortcode_default_value = isset($shortcode_details['default'])
          ? $shortcode_details['default'] : false;
        if(!class_exists($shortcode_class)) return false;
        return $shortcode_class::process(
          $shortcode_action,
          $shortcode_default_value,
          $this->newsletter,
          $this->subscriber,
          $this->queue,
          $content
        );
      }, $shortcodes);
    return $processed_shortcodes;
  }

  function replace($content, $limit = false) {
    $shortcodes = $this->extract($content, $limit);
    $processed_shortcodes = $this->process($shortcodes, $content);
    $shortcodes = array_intersect_key($shortcodes, $processed_shortcodes);
    return str_replace($shortcodes, $processed_shortcodes, $content);
  }
}
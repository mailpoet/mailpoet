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
      $newsletter->toArray() :
      $newsletter;
    $this->subscriber = (is_object($subscriber)) ?
      $subscriber->toArray() :
      $subscriber;
    $this->queue = (is_object($queue)) ?
      $queue->toArray() :
      $queue;
  }

  function extract($text, $limit = false) {
    $limit = (is_array($limit)) ? implode('|', $limit) : false;
    $regex = sprintf(
      '/\[%s:.*?\]/ism',
      ($limit) ? '(?:' . $limit . ')' : '(?:\w+)'
    );
    preg_match_all($regex, $text, $shortcodes);
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

  function process($shortcodes, $text) {
    $processed_shortcodes = array_map(
      function($shortcode) use($text) {
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
          $text,
          $shortcode
        );
      }, $shortcodes);
    return $processed_shortcodes;
  }

  function replace($text) {
    $shortcodes = $this->extract($text);
    $processed_shortcodes = $this->process($shortcodes, $text);
    $shortcodes = array_intersect_key($shortcodes, $processed_shortcodes);
    return str_replace($shortcodes, $processed_shortcodes, $text);
  }
}
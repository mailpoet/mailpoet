<?php
namespace MailPoet\Newsletter\Shortcodes;

class Shortcodes {
  public $rendered_newsletter;
  public $newsletter;
  public $subscriber;

  function __construct(
    $rendered_newsletter,
    $newsletter = false,
    $subscriber = false) {
    $this->rendered_newsletter = $rendered_newsletter;
    $this->newsletter = $newsletter;
    $this->subscriber = $subscriber;
  }

  function extract() {
    preg_match_all('/\[(?:\w+):.*?\]/', $this->rendered_newsletter, $shortcodes);
    return array_unique($shortcodes[0]);
  }

  function process($shortcodes) {
    $processed_shortcodes = array_map(
      function ($shortcode) {
        preg_match(
          '/\[(?P<type>\w+):(?P<action>\w+)(?:.*?default:(?P<default>.*?))?\]/',
          $shortcode,
          $shortcode_details
        );

        // TODO: discuss renaming "global". It is a reserved name in PHP.
        if(
          isset($shortcode_details['type'])
          && $shortcode_details['type'] === 'global'
        ) {
          $shortcode_details['type'] = 'link';
        }

        $shortcode_class =
          __NAMESPACE__ . '\\Categories\\' . ucfirst($shortcode_details['type']);
        if(!class_exists($shortcode_class)) return false;
        return $shortcode_class::process(
          $shortcode_details['action'],
          isset($shortcode_details['default'])
            ? $shortcode_details['default'] : false,
          $this->newsletter,
          $this->subscriber
        );
      }, $shortcodes);
    return $processed_shortcodes;
  }

  function replace() {
    $shortcodes = $this->extract($this->rendered_newsletter);
    $processed_shortcodes = $this->process($shortcodes);
    $shortcodes = array_intersect_key($shortcodes, $processed_shortcodes);
    return str_replace($shortcodes, $processed_shortcodes, $this->rendered_newsletter);
  }
}
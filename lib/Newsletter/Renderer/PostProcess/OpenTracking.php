<?php
namespace MailPoet\Newsletter\Renderer\PostProcess;

use MailPoet\Newsletter\Links\Links;
use MailPoet\Newsletter\Renderer\Renderer;

class OpenTracking {
  static function process($template) {
    $DOM = new \pQuery();
    $DOM = $DOM->parseStr($template);
    $template = $DOM->query('body');
    $open_tracking_image = sprintf(
      '<img alt="" class="" src="%s/%s"/>',
      home_url(),
      esc_attr('?mailpoet&endpoint=track&action=open&data=' . Links::DATA_TAG)
    );
    $template->html($template->html() . $open_tracking_image);
    return $DOM->__toString();
  }

  static function addTrackingImage() {
    add_filter(Renderer::POST_PROCESS_FILTER, function ($template) {
      return OpenTracking::process($template);
    });
    return true;
  }
}
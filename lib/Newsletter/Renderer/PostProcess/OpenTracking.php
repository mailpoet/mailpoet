<?php
namespace MailPoet\Newsletter\Renderer\PostProcess;

use MailPoet\Newsletter\Links\Links;
use MailPoet\Newsletter\Renderer\Renderer;

class OpenTracking {
  static function process($template) {
    $DOM = new \pQuery();
    $DOM = $DOM->parseStr($template);
    $template = $DOM->query('body');
    // url is a temporary data tag that will be further replaced with
    // the proper track API URL during sending
    $url = Links::DATA_TAG_OPEN;
    $open_tracking_image = sprintf(
      '<img alt="" class="" src="%s"/>',
      $url
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
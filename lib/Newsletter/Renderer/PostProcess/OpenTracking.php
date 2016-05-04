<?php
namespace MailPoet\Newsletter\Renderer\PostProcess;

use MailPoet\Newsletter\Links\Links;

class OpenTracking {
  static function process($template) {
    $DOM = new \pQuery();
    $DOM = $DOM->parseStr($template);
    $template = $DOM->query('body');
    $open_tracking_link = sprintf(
      '<img alt="" class="" src="%s/%s"/>',
      home_url(),
      esc_attr('?mailpoet&endpoint=track&action=open&data=' . Links::DATA_TAG)
    );
    $template->html($template->html() . $open_tracking_link);
    return $DOM->__toString();
  }
}
<?php
namespace MailPoet\Newsletter\Renderer\PostProcess;

class OpenTracking {
  static function process($template) {
    $DOM = new \pQuery();
    $DOM = $DOM->parseStr($template);
    $template = $DOM->query('body');
    $open_tracking_link = sprintf(
      '<img alt="" class="" src="%s/%s"/>',
      home_url(),
      htmlentities('?mailpoet&endpoint=track&action=open&data=[mailpoet_data]')
    );
    $template->html($template->html() . $open_tracking_link);
    return $DOM->__toString();
  }
}
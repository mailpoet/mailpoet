<?php
namespace MailPoet\Newsletter\Renderer\PostProcess;

class OpenTracking {
  static function process($template, $user_id) {
    $DOM = new \pQuery();
    $template = $DOM->query('body');
    $open_tracking_link = sprintf(
      '<img alt="" src="%s/?mailpoet&endpoint=track&action=open&data=[mailpoet_data]',
      home_url()
    );
    $template->html($template->html() . $open_tracking_link);
    return $DOM->__toString();
  }
}
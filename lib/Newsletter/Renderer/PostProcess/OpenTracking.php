<?php
namespace MailPoet\Newsletter\Renderer\PostProcess;

use MailPoet\API\API;
use MailPoet\API\Endpoints\Track as TrackAPI;
use MailPoet\Newsletter\Links\Links;
use MailPoet\Newsletter\Renderer\Renderer;

class OpenTracking {
  static function process($template) {
    $DOM = new \pQuery();
    $DOM = $DOM->parseStr($template);
    $template = $DOM->query('body');
    $url = API::buildRequest(
      TrackAPI::ENDPOINT,
      TrackAPI::ACTION_OPEN,
      Links::DATA_TAG
    );
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

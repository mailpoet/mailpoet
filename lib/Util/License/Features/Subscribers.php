<?php
namespace MailPoet\Util\License\Features;

use MailPoet\Config\Renderer;
use MailPoet\Models\Subscriber as SubscriberModel;
use MailPoet\Util\License\License;

class Subscribers {
  static $subscribers_limit = 2000;

  function check() {
    if(!License::getLicense() &&
      SubscriberModel::getTotalSubscribers() > self::$subscribers_limit
    ) {
      $renderer = new Renderer();
      echo $renderer->init()->render('limit.html', array(
        'limit' => self::$subscribers_limit
      ));
      return $this->terminateRequest();
    }
  }

  function terminateRequest() {
    exit;
  }
}
<?php

namespace MailPoet\Config;

use MailPoet\Subscribers\SubscriberPersonalDataEraser;
use MailPoet\WP\Functions as WPFunctions;

class PersonalDataErasers {

  function init() {
    WPFunctions::get()->addFilter('wp_privacy_personal_data_erasers', array($this, 'registerSubscriberEraser'));
  }

  function registerSubscriberEraser($erasers) {
    $erasers['mailpet-subscriber'] = array(
      'eraser_friendly_name' => WPFunctions::get()->__('MailPoet Subscribers', 'mailpoet'),
      'callback' => array(new SubscriberPersonalDataEraser(), 'erase'),
    );

    return $erasers;
  }

}
<?php

namespace MailPoet\Config;

use MailPoet\Subscribers\SubscriberPersonalDataEraser;

class PersonalDataErasers {

  function init() {
    add_filter('wp_privacy_personal_data_erasers', array($this, 'registerSubscriberEraser'));
  }

  function registerSubscriberEraser($erasers) {
    $erasers['mailpet-subscriber'] = array(
      'eraser_friendly_name' => __('MailPoet Subscribers', 'mailpoet'),
      'callback' => array(new SubscriberPersonalDataEraser(), 'erase'),
    );

    return $erasers;
  }

}
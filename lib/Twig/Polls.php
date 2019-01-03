<?php

namespace MailPoet\Twig;

use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Polls extends \Twig_Extension {
  public function getFunctions() {
    return array(
      new \Twig_SimpleFunction(
        'get_polls_data',
        array($this, 'getPollsData'),
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'get_polls_visiblity',
        array($this, 'getPollsVisibility'),
        array('is_safe' => array('all'))
      ),
    );
  }

  function getPollsData() {
    return [
      'mta_method' => Setting::getValue('mta.method'),
    ];
  }

  function getPollsVisibility() {
    return [
      'show_poll_success_delivery_preview' => Setting::getValue('show_poll_success_delivery_preview'),
    ];
  }
}

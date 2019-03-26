<?php

namespace MailPoet\Twig;

if (!defined('ABSPATH')) exit;

class Helpscout extends \MailPoetVendor\Twig_Extension {
  public function getFunctions() {
    return array(
      new \MailPoetVendor\Twig_SimpleFunction(
        'get_helpscout_data',
        '\MailPoet\Helpscout\Beacon::getData',
        array('is_safe' => array('all'))
      )
    );
  }
}

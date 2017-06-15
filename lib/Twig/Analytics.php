<?php
namespace MailPoet\Twig;

use MailPoet\Analytics\Reporter;
use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Analytics extends \Twig_Extension {

  public function getName() {
    return 'analytics';
  }

  public function getFunctions() {
    $analytics = new \MailPoet\Analytics\Analytics(new Reporter());
    return array(
      new \Twig_SimpleFunction(
        'get_analytics_data',
        array($analytics, 'generateAnalytics'),
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'is_analytics_enabled',
        array($analytics, 'isEnabled'),
        array('is_safe' => array('all'))
      ),
    );
  }
}

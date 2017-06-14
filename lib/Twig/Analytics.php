<?php
namespace MailPoet\Twig;

if(!defined('ABSPATH')) exit;

class Analytics extends \Twig_Extension {

  public function getName() {
    return 'anaylytics';
  }

  public function getFunctions() {
    $analytics = new \MailPoet\Config\Analytics;
    return array(
      new \Twig_SimpleFunction(
        'get_analytics_data',
        array($analytics, 'getData'),
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

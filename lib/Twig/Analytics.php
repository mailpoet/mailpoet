<?php

namespace MailPoet\Twig;

use MailPoet\Analytics\Reporter;
use MailPoet\Analytics\Analytics as AnalyticsGenerator;
use MailPoet\Settings\SettingsController;

if(!defined('ABSPATH')) exit;

class Analytics extends \Twig_Extension {
  public function getFunctions() {
    $settings = new SettingsController();
    $analytics = new AnalyticsGenerator(new Reporter($settings), $settings);
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
      new \Twig_SimpleFunction(
        'get_analytics_public_id',
        array($analytics, 'getPublicId'),
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'is_analytics_public_id_new',
        array($analytics, 'isPublicIdNew'),
        array('is_safe' => array('all'))
      )
    );
  }
}

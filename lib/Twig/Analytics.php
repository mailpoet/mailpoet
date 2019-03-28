<?php

namespace MailPoet\Twig;

use MailPoet\Analytics\Reporter;
use MailPoet\Analytics\Analytics as AnalyticsGenerator;
use MailPoet\Settings\SettingsController;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoetVendor\Twig\Extension\AbstractExtension;
use MailPoetVendor\Twig\TwigFunction;

if (!defined('ABSPATH')) exit;

class Analytics extends AbstractExtension {
  public function getFunctions() {
    $settings = new SettingsController();
    $analytics = new AnalyticsGenerator(new Reporter($settings, new WooCommerceHelper), $settings);
    return array(
      new TwigFunction(
        'get_analytics_data',
        array($analytics, 'generateAnalytics'),
        array('is_safe' => array('all'))
      ),
      new TwigFunction(
        'is_analytics_enabled',
        array($analytics, 'isEnabled'),
        array('is_safe' => array('all'))
      ),
      new TwigFunction(
        'get_analytics_public_id',
        array($analytics, 'getPublicId'),
        array('is_safe' => array('all'))
      ),
      new TwigFunction(
        'is_analytics_public_id_new',
        array($analytics, 'isPublicIdNew'),
        array('is_safe' => array('all'))
      )
    );
  }
}

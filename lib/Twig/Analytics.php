<?php

namespace MailPoet\Twig;

use MailPoet\Analytics\Analytics as AnalyticsGenerator;
use MailPoet\Analytics\Reporter;
use MailPoet\Settings\SettingsController;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Twig\Extension\AbstractExtension;
use MailPoetVendor\Twig\TwigFunction;

class Analytics extends AbstractExtension {
  public function getFunctions() {
    $settings = new SettingsController();
    $analytics = new AnalyticsGenerator(
      new Reporter($settings, new WooCommerceHelper()),
      $settings
    );
    return [
      new TwigFunction(
        'get_analytics_data',
        [$analytics, 'generateAnalytics'],
        ['is_safe' => ['all']]
      ),
      new TwigFunction(
        'is_analytics_enabled',
        [$analytics, 'isEnabled'],
        ['is_safe' => ['all']]
      ),
      new TwigFunction(
        'get_analytics_public_id',
        [$analytics, 'getPublicId'],
        ['is_safe' => ['all']]
      ),
      new TwigFunction(
        'is_analytics_public_id_new',
        [$analytics, 'isPublicIdNew'],
        ['is_safe' => ['all']]
      ),
    ];
  }
}

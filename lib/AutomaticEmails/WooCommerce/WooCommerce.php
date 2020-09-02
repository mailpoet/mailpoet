<?php

namespace MailPoet\AutomaticEmails\WooCommerce;

use MailPoet\AutomaticEmails\AutomaticEmails;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;

class WooCommerce {
  const SLUG = 'woocommerce';
  const EVENTS_FILTER = 'mailpoet_woocommerce_events';

  /** @var WooCommerceHelper */
  private $woocommerceHelper;

  public $availableEvents = [
    'AbandonedCart',
    'FirstPurchase',
    'PurchasedInCategory',
    'PurchasedProduct',
  ];
  private $woocommerceEnabled;
  private $wp;

  public function __construct() {
    $this->wp = new WPFunctions;
    $this->woocommerceHelper = new WooCommerceHelper();
    $this->woocommerceEnabled = $this->isWoocommerceEnabled();
  }

  public function init() {
    $this->wp->addFilter(
      AutomaticEmails::FILTER_PREFIX . self::SLUG,
      [
        $this,
        'setupGroup',
      ]
    );
    $this->wp->addFilter(
      self::EVENTS_FILTER,
      [
        $this,
        'setupEvents',
      ]
    );
  }

  public function setupGroup() {
    return [
      'slug' => self::SLUG,
      'title' => WPFunctions::get()->__('WooCommerce', 'mailpoet'),
      'description' => WPFunctions::get()->__('Automatically send an email based on your customers’ purchase behavior. Enhance your customer service and start increasing sales with WooCommerce follow up emails.', 'mailpoet'),
      'events' => $this->wp->applyFilters(self::EVENTS_FILTER, []),
    ];
  }

  public function setupEvents($events) {
    $customEventDetails = (!$this->woocommerceEnabled) ? [
      'actionButtonTitle' => WPFunctions::get()->__('WooCommerce is required', 'mailpoet'),
      'actionButtonLink' => 'https://wordpress.org/plugins/woocommerce/',
    ] : [];

    foreach ($this->availableEvents as $event) {
      $eventClass = sprintf(
        '%s\Events\%s',
        __NAMESPACE__,
        $event
      );

      if (!class_exists($eventClass)) {
        $this->displayEventWarning($event);
        continue;
      }

      $eventInstance = new $eventClass();

      if (method_exists($eventInstance, 'init')) {
        $eventInstance->init();
      } else {
        $this->displayEventWarning($event);
        continue;
      }

      if (method_exists($eventInstance, 'getEventDetails')) {
        $eventDetails = array_merge($eventInstance->getEventDetails(), $customEventDetails);
      } else {
        $this->displayEventWarning($event);
        continue;
      }
      $events[] = $eventDetails;
    }

    return $events;
  }

  public function isWoocommerceEnabled() {
    return $this->woocommerceHelper->isWooCommerceActive();
  }

  private function displayEventWarning($event) {
    $notice = sprintf('%s %s',
      sprintf(__('WooCommerce %s event is misconfigured.', 'mailpoet'), $event),
      WPFunctions::get()->__('Please contact our technical support for assistance.', 'mailpoet')
    );
    Notice::displayWarning($notice);
  }
}

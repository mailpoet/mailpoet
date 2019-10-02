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
  private $woocommerce_helper;

  public $available_events = [
    'AbandonedCart',
    'FirstPurchase',
    'PurchasedInCategory',
    'PurchasedProduct',
  ];
  private $_woocommerce_enabled;
  private $wp;

  function __construct() {
    $this->wp = new WPFunctions;
    $this->woocommerce_helper = new WooCommerceHelper();
    $this->_woocommerce_enabled = $this->isWoocommerceEnabled();
  }

  function init() {
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

  function setupGroup() {
    return [
      'slug' => self::SLUG,
      'title' => WPFunctions::get()->__('WooCommerce', 'mailpoet'),
      'description' => WPFunctions::get()->__('Automatically send an email when there is a new WooCommerce product, order and some other action takes place.', 'mailpoet'),
      'events' => $this->wp->applyFilters(self::EVENTS_FILTER, []),
    ];
  }

  function setupEvents($events) {
    $custom_event_details = (!$this->_woocommerce_enabled) ? [
      'actionButtonTitle' => WPFunctions::get()->__('WooCommerce is required', 'mailpoet'),
      'actionButtonLink' => 'https://wordpress.org/plugins/woocommerce/',
    ] : [];

    foreach ($this->available_events as $event) {
      $event_class = sprintf(
        '%s\Events\%s',
        __NAMESPACE__,
        $event
      );

      if (!class_exists($event_class)) {
        $this->displayEventWarning($event);
        continue;
      }

      $event_instance = new $event_class();

      if (method_exists($event_instance, 'init')) {
        $event_instance->init();
      } else {
        $this->displayEventWarning($event);
        continue;
      }

      if (method_exists($event_instance, 'getEventDetails')) {
        $event_details = array_merge($event_instance->getEventDetails(), $custom_event_details);
      } else {
        $this->displayEventWarning($event);
        continue;
      }
      $events[] = $event_details;
    }

    return $events;
  }

  function isWoocommerceEnabled() {
    return $this->woocommerce_helper->isWooCommerceActive();
  }

  private function displayEventWarning($event) {
    $notice = sprintf('%s %s',
      sprintf(__('WooCommerce %s event is misconfigured.', 'mailpoet'), $event),
      WPFunctions::get()->__('Please contact our technical support for assistance.', 'mailpoet')
    );
    Notice::displayWarning($notice);
  }
}

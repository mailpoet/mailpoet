<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

use MailPoet\WP\Functions as WPFunctions;

class OrderAttributionFields {
  const FIELD_CLICK_ID = 'mailpoet_click_id';
  const FIELD_NEWSLETTER_ID = 'mailpoet_newsletter_id';
  const FIELD_QUEUE_ID = 'mailpoet_queue_id';
  const FIELD_SUBSCRIBER_ID = 'mailpoet_subscriber_id';

  const FIELD_NAMES = [
    self::FIELD_CLICK_ID,
    self::FIELD_NEWSLETTER_ID,
    self::FIELD_QUEUE_ID,
    self::FIELD_SUBSCRIBER_ID,
  ];

  /** @var WPFunctions */
  private $wp;

  /** @var Helper */
  private $wooHelper;

  public function __construct(
    WPFunctions $wp,
    Helper $wooHelper
  ) {
    $this->wp = $wp;
    $this->wooHelper = $wooHelper;
  }

  /**
   * WooCommerce applies the filter while its plugin file is being loaded,
   * so this must run before WooCommerce loads (MailPoet loads first
   * because WordPress loads active plugins in alphabetical order).
   */
  public function setup(): void {
    $this->wp->addFilter(
      'wc_order_attribution_tracking_fields',
      [$this, 'registerTrackingFields']
    );
  }

  /**
   * @param mixed $fields
   * @return mixed
   */
  public function registerTrackingFields($fields) {
    if (!is_array($fields) || !$this->wooHelper->isWooCommerceActive()) {
      return $fields;
    }
    foreach (self::FIELD_NAMES as $fieldName) {
      // The value is a sourcebuster.js accessor path. An empty path resolves to null
      // client-side, so the fields stay empty placeholders until MailPoet writes
      // values server-side via the woocommerce_order_save_attribution_data action.
      $fields[$fieldName] = '';
    }
    return $fields;
  }
}

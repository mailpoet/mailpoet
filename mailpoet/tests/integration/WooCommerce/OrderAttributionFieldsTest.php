<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

use Automattic\WooCommerce\Internal\Orders\OrderAttributionController;

/**
 * @group woo
 */
class OrderAttributionFieldsTest extends \MailPoetTest {
  public function testItRegistersTheFilterDuringPluginBootstrap(): void {
    $orderAttributionFields = $this->diContainer->get(OrderAttributionFields::class);
    verify(has_filter(
      'wc_order_attribution_tracking_fields',
      [$orderAttributionFields, 'registerTrackingFields']
    ))->notEmpty();
  }

  public function testWooCommerceAttributionKnowsMailPoetFields(): void {
    foreach (OrderAttributionFields::FIELD_NAMES as $fieldName) {
      verify($this->getWooAttributionFieldNames())->arrayContains($fieldName);
    }
  }

  public function testWooCommerceStandardFieldsAreUnchanged(): void {
    $fieldNames = $this->getWooAttributionFieldNames();
    $standardFields = [
      'source_type',
      'referrer',
      'utm_campaign',
      'utm_source',
      'utm_medium',
      'utm_source_platform',
    ];
    foreach ($standardFields as $fieldName) {
      verify($fieldNames)->arrayContains($fieldName);
    }
  }

  /**
   * Reads the field names cached by WooCommerce's attribution controller when
   * WooCommerce loaded. Both the classic checkout hidden inputs and the Block
   * checkout Store API schema are built from these names, so they prove the
   * filter was registered early enough (before the WooCommerce plugin file
   * loaded and applied it).
   */
  private function getWooAttributionFieldNames(): array {
    return wc_get_container()->get(OrderAttributionController::class)->get_field_names();
  }
}

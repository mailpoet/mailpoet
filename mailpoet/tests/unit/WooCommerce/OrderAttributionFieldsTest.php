<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\WP\Functions as WPFunctions;

class OrderAttributionFieldsTest extends \MailPoetUnitTest {
  private const WOO_DEFAULT_FIELDS = [
    'source_type' => 'current.typ',
    'referrer' => 'current_add.rf',
    'utm_campaign' => 'current.cmp',
    'utm_source' => 'current.src',
    'utm_medium' => 'current.mdm',
  ];

  public function testItRegistersTheTrackingFieldsFilter(): void {
    $wp = Stub::make(WPFunctions::class, [
      'addFilter' => Expected::once(function ($tag, $callback) {
        verify($tag)->equals('wc_order_attribution_tracking_fields');
        verify($callback)->notEmpty();
      }),
    ], $this);
    $orderAttributionFields = new OrderAttributionFields($wp, Stub::make(Helper::class));

    $orderAttributionFields->setup();
  }

  public function testItAddsMailPoetFieldsAndKeepsWooFieldsUnchanged(): void {
    $orderAttributionFields = $this->createOrderAttributionFields(true);

    $fields = (array)$orderAttributionFields->registerTrackingFields(self::WOO_DEFAULT_FIELDS);

    foreach (OrderAttributionFields::FIELD_NAMES as $fieldName) {
      verify($fields)->arrayHasKey($fieldName);
      verify($fields[$fieldName])->equals('');
    }
    foreach (self::WOO_DEFAULT_FIELDS as $fieldName => $accessor) {
      verify($fields[$fieldName])->equals($accessor);
    }
    verify($fields)->arrayCount(count(self::WOO_DEFAULT_FIELDS) + count(OrderAttributionFields::FIELD_NAMES));
  }

  public function testItBuildsWooAttributionMetaKeys(): void {
    verify(OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_CLICK_ID))
      ->equals('_wc_order_attribution_mailpoet_click_id');
    verify(OrderAttributionFields::getMetaKey('utm_source'))->equals('_wc_order_attribution_utm_source');
  }

  public function testItBuildsMultipleWooAttributionMetaKeys(): void {
    verify(OrderAttributionFields::getMetaKeys([
      OrderAttributionFields::FIELD_NEWSLETTER_ID,
      OrderAttributionFields::FIELD_SUBSCRIBER_ID,
    ]))->equals([
      '_wc_order_attribution_mailpoet_newsletter_id',
      '_wc_order_attribution_mailpoet_subscriber_id',
    ]);
  }

  public function testItDoesNotAddFieldsWhenWooCommerceIsNotActive(): void {
    $orderAttributionFields = $this->createOrderAttributionFields(false);

    $fields = $orderAttributionFields->registerTrackingFields(self::WOO_DEFAULT_FIELDS);

    verify($fields)->equals(self::WOO_DEFAULT_FIELDS);
  }

  public function testItReturnsNonArrayValuesUnchanged(): void {
    $orderAttributionFields = $this->createOrderAttributionFields(true);

    verify($orderAttributionFields->registerTrackingFields(null))->null();
    verify($orderAttributionFields->registerTrackingFields('invalid'))->equals('invalid');
  }

  private function createOrderAttributionFields(bool $isWooCommerceActive): OrderAttributionFields {
    $wooHelper = Stub::make(Helper::class, [
      'isWooCommerceActive' => $isWooCommerceActive,
    ], $this);
    return new OrderAttributionFields(Stub::make(WPFunctions::class), $wooHelper);
  }
}

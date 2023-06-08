<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WooCommerce\Fields;

use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Engine\WordPress;
use MailPoet\Automation\Integrations\WooCommerce\Payloads\OrderPayload;
use MailPoet\Automation\Integrations\WooCommerce\WooCommerce;
use WC_Payment_Gateway;
use WP_Post;

class OrderFieldsFactory {
  /** @var WordPress */
  private $wordPress;

  /** @var WooCommerce */
  private $wooCommerce;

  public function __construct(
    WordPress $wordPress,
    WooCommerce $wooCommerce
  ) {
    $this->wordPress = $wordPress;
    $this->wooCommerce = $wooCommerce;
  }

  /** @return Field[] */
  public function getFields(): array {
    return array_merge(
      [
        new Field(
          'woocommerce:order:billing-company',
          Field::TYPE_STRING,
          __('Billing company', 'mailpoet'),
          function (OrderPayload $payload) {
            return $payload->getOrder()->get_billing_company();
          }
        ),
        new Field(
          'woocommerce:order:billing-phone',
          Field::TYPE_STRING,
          __('Billing phone', 'mailpoet'),
          function (OrderPayload $payload) {
            return $payload->getOrder()->get_billing_phone();
          }
        ),
        new Field(
          'woocommerce:order:billing-city',
          Field::TYPE_STRING,
          __('Billing city', 'mailpoet'),
          function (OrderPayload $payload) {
            return $payload->getOrder()->get_billing_city();
          }
        ),
        new Field(
          'woocommerce:order:billing-postcode',
          Field::TYPE_STRING,
          __('Billing postcode', 'mailpoet'),
          function (OrderPayload $payload) {
            return $payload->getOrder()->get_billing_postcode();
          }
        ),
        new Field(
          'woocommerce:order:billing-state',
          Field::TYPE_STRING,
          __('Billing state/county', 'mailpoet'),
          function (OrderPayload $payload) {
            return $payload->getOrder()->get_billing_state();
          }
        ),
        new Field(
          'woocommerce:order:billing-country',
          Field::TYPE_STRING,
          __('Billing country', 'mailpoet'),
          function (OrderPayload $payload) {
            return $payload->getOrder()->get_billing_country();
          }
        ),
        new Field(
          'woocommerce:order:shipping-company',
          Field::TYPE_STRING,
          __('Shipping company', 'mailpoet'),
          function (OrderPayload $payload) {
            return $payload->getOrder()->get_shipping_company();
          }
        ),
        new Field(
          'woocommerce:order:shipping-phone',
          Field::TYPE_STRING,
          __('Shipping phone', 'mailpoet'),
          function (OrderPayload $payload) {
            return $payload->getOrder()->get_shipping_phone();
          }
        ),
        new Field(
          'woocommerce:order:shipping-city',
          Field::TYPE_STRING,
          __('Shipping city', 'mailpoet'),
          function (OrderPayload $payload) {
            return $payload->getOrder()->get_shipping_city();
          }
        ),
        new Field(
          'woocommerce:order:shipping-postcode',
          Field::TYPE_STRING,
          __('Shipping postcode', 'mailpoet'),
          function (OrderPayload $payload) {
            return $payload->getOrder()->get_shipping_postcode();
          }
        ),
        new Field(
          'woocommerce:order:shipping-state',
          Field::TYPE_STRING,
          __('Shipping state/county', 'mailpoet'),
          function (OrderPayload $payload) {
            return $payload->getOrder()->get_shipping_state();
          }
        ),
        new Field(
          'woocommerce:order:shipping-country',
          Field::TYPE_STRING,
          __('Shipping country', 'mailpoet'),
          function (OrderPayload $payload) {
            return $payload->getOrder()->get_shipping_country();
          }
        ),
        new Field(
          'woocommerce:order:created-date',
          Field::TYPE_DATETIME,
          __('Created date', 'mailpoet'),
          function (OrderPayload $payload) {
            return $payload->getOrder()->get_date_created();
          }
        ),
        new Field(
          'woocommerce:order:paid-date',
          Field::TYPE_DATETIME,
          __('Paid date', 'mailpoet'),
          function (OrderPayload $payload) {
            return $payload->getOrder()->get_date_paid();
          }
        ),
        new Field(
          'woocommerce:order:customer-note',
          Field::TYPE_STRING,
          __('Customer provided note', 'mailpoet'),
          function (OrderPayload $payload) {
            return $payload->getOrder()->get_customer_note();
          }
        ),
        new Field(
          'woocommerce:order:payment-method',
          Field::TYPE_ENUM,
          __('Payment method', 'mailpoet'),
          function (OrderPayload $payload) {
            return $payload->getOrder()->get_payment_method();
          },
          [
            'options' => $this->getOrderPaymentOptions(),
          ]
        ),
        new Field(
          'woocommerce:order:status',
          Field::TYPE_ENUM,
          __('Status', 'mailpoet'),
          function (OrderPayload $payload) {
            return $payload->getOrder()->get_status();
          },
          [
            'options' => $this->getOrderStatusOptions(),
          ]
        ),
        new Field(
          'woocommerce:order:total',
          Field::TYPE_NUMBER,
          __('Total', 'mailpoet'),
          function (OrderPayload $payload) {
            return (float)$payload->getOrder()->get_total();
          }
        ),
        new Field(
          'woocommerce:order:coupons',
          Field::TYPE_ENUM_ARRAY,
          __('Used coupons', 'mailpoet'),
          function (OrderPayload $payload) {
            return $payload->getOrder()->get_coupon_codes();
          },
          [
            'options' => $this->getCouponOptions(),
          ]
        ),
      ]
    );
  }

  private function getOrderPaymentOptions(): array {
    $gateways = WC()->payment_gateways()->get_available_payment_gateways();
    $options = [];
    foreach ($gateways as $gateway) {
      if ($gateway instanceof WC_Payment_Gateway && $gateway->enabled === 'yes') {
        $options[] = ['id' => $gateway->id, 'name' => $gateway->title];
      }
    }
    return $options;
  }

  private function getOrderStatusOptions(): array {
    $statuses = $this->wooCommerce->wcGetOrderStatuses();
    $options = [];
    foreach ($statuses as $id => $name) {
      $options[] = ['id' => $id, 'name' => $name];
    }
    return $options;
  }

  private function getCouponOptions(): array {
    $coupons = $this->wordPress->getPosts([
      'post_type' => 'shop_coupon',
      'post_status' => 'publish',
      'posts_per_page' => -1,
      'orderby' => 'name',
      'order' => 'asc',
    ]);

    $options = [];
    foreach ($coupons as $coupon) {
      if ($coupon instanceof WP_Post) {
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $options[] = ['id' => $coupon->post_title, 'name' => $coupon->post_name];
      }
    }
    return $options;
  }
}

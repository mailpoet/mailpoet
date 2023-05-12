<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WooCommerce\Fields;

use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Integrations\WooCommerce\Payloads\CustomerPayload;

class CustomerFieldsFactory {
  /** @return Field[] */
  public function getFields(): array {
    return [
      new Field(
        'woocommerce:customer:billing-company',
        Field::TYPE_STRING,
        __('Billing company', 'mailpoet'),
        function (CustomerPayload $payload) {
          $customer = $payload->getCustomer();
          return $customer ? $customer->get_billing_company() : null;
        }
      ),
      new Field(
        'woocommerce:customer:billing-phone',
        Field::TYPE_STRING,
        __('Billing phone', 'mailpoet'),
        function (CustomerPayload $payload) {
          $customer = $payload->getCustomer();
          return $customer ? $customer->get_billing_phone() : null;
        }
      ),
      new Field(
        'woocommerce:customer:billing-city',
        Field::TYPE_STRING,
        __('Billing city', 'mailpoet'),
        function (CustomerPayload $payload) {
          $customer = $payload->getCustomer();
          return $customer ? $customer->get_billing_city() : null;
        }
      ),
      new Field(
        'woocommerce:customer:billing-postcode',
        Field::TYPE_STRING,
        __('Billing postcode', 'mailpoet'),
        function (CustomerPayload $payload) {
          $customer = $payload->getCustomer();
          return $customer ? $customer->get_billing_postcode() : null;
        }
      ),
      new Field(
        'woocommerce:customer:billing-state',
        Field::TYPE_STRING,
        __('Billing state/county', 'mailpoet'),
        function (CustomerPayload $payload) {
          $customer = $payload->getCustomer();
          return $customer ? $customer->get_billing_state() : null;
        }
      ),
      new Field(
        'woocommerce:customer:billing-country',
        Field::TYPE_STRING,
        __('Billing country', 'mailpoet'),
        function (CustomerPayload $payload) {
          $customer = $payload->getCustomer();
          return $customer ? $customer->get_billing_country() : null;
        }
      ),
      new Field(
        'woocommerce:customer:shipping-company',
        Field::TYPE_STRING,
        __('Shipping company', 'mailpoet'),
        function (CustomerPayload $payload) {
          $customer = $payload->getCustomer();
          return $customer ? $customer->get_shipping_company() : null;
        }
      ),
      new Field(
        'woocommerce:customer:shipping-phone',
        Field::TYPE_STRING,
        __('Shipping phone', 'mailpoet'),
        function (CustomerPayload $payload) {
          $customer = $payload->getCustomer();
          return $customer ? $customer->get_shipping_phone() : null;
        }
      ),
      new Field(
        'woocommerce:customer:shipping-city',
        Field::TYPE_STRING,
        __('Shipping city', 'mailpoet'),
        function (CustomerPayload $payload) {
          $customer = $payload->getCustomer();
          return $customer ? $customer->get_shipping_city() : null;
        }
      ),
      new Field(
        'woocommerce:customer:shipping-postcode',
        Field::TYPE_STRING,
        __('Shipping postcode', 'mailpoet'),
        function (CustomerPayload $payload) {
          $customer = $payload->getCustomer();
          return $customer ? $customer->get_shipping_postcode() : null;
        }
      ),
      new Field(
        'woocommerce:customer:shipping-state',
        Field::TYPE_STRING,
        __('Shipping state/county', 'mailpoet'),
        function (CustomerPayload $payload) {
          $customer = $payload->getCustomer();
          return $customer ? $customer->get_shipping_state() : null;
        }
      ),
      new Field(
        'woocommerce:customer:shipping-country',
        Field::TYPE_STRING,
        __('Shipping country', 'mailpoet'),
        function (CustomerPayload $payload) {
          $customer = $payload->getCustomer();
          return $customer ? $customer->get_shipping_country() : null;
        }
      ),
    ];
  }
}

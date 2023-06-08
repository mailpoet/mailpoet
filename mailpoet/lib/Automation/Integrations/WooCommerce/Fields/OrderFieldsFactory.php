<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WooCommerce\Fields;

use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Integrations\WooCommerce\Payloads\OrderPayload;

class OrderFieldsFactory {
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
      ]
    );
  }
}

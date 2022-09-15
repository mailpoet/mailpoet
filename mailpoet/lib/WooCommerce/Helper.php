<?php

namespace MailPoet\WooCommerce;

use MailPoet\DI\ContainerWrapper;
use MailPoet\WP\Functions as WPFunctions;

class Helper {
  public function isWooCommerceActive() {
    return class_exists('WooCommerce');
  }

  public function isWooCommerceBlocksActive($min_version = '') {
    if (!class_exists('\Automattic\WooCommerce\Blocks\Package')) {
      return false;
    }
    if ($min_version) {
      return version_compare(\Automattic\WooCommerce\Blocks\Package::get_version(), $min_version, '>=');
    }
    return true;
  }

  public function isWooCommerceCustomOrdersTableEnabled(): bool {
    if (
      $this->isWooCommerceActive()
      && method_exists('\Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled')
      && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()
    ) {
      return true;
    }

    return false;
  }

  public function WC() {
    return WC();
  }

  public function wcGetCustomerOrderCount($userId) {
    return wc_get_customer_order_count($userId);
  }

  public function wcGetOrder($order = false) {
    return wc_get_order($order);
  }

  public function wcGetOrders(array $args) {
    return wc_get_orders($args);
  }

  public function wcCreateOrder(array $args) {
    return wc_create_order($args);
  }

  public function wcPrice($price, array $args = []) {
    return wc_price($price, $args);
  }

  public function wcGetProduct($theProduct = false) {
    return wc_get_product($theProduct);
  }

  public function getWoocommerceCurrency() {
    return get_woocommerce_currency();
  }

  public function getWoocommerceCurrencySymbol() {
    return get_woocommerce_currency_symbol();
  }

  public function woocommerceFormField($key, $args, $value) {
    return woocommerce_form_field($key, $args, $value);
  }

  public function wcLightOrDark($color, $dark, $light) {
    return wc_light_or_dark($color, $dark, $light);
  }

  public function wcHexIsLight($color) {
    return wc_hex_is_light($color);
  }

  public function getOrdersCountCreatedBefore(string $dateTime): int {
    $ordersCount = $this->wcGetOrders([
      'status' => 'all',
      'type' => 'shop_order',
      'date_created' => '<' . $dateTime,
      'limit' => 1,
      'paginate' => true,
    ])->total;

    return $ordersCount;
  }

  public function getRawPrice($price, array $args = []) {
    $htmlPrice = $this->wcPrice($price, $args);
    return html_entity_decode(strip_tags($htmlPrice));
  }

  public function getAllowedCountries(): array {
    return (new \WC_Countries)->get_allowed_countries() ?? [];
  }

  public function wasMailPoetInstalledViaWooCommerceOnboardingWizard(): bool {
    $wp = ContainerWrapper::getInstance()->get(WPFunctions::class);
    $installedViaWooCommerce = false;
    $wooCommerceOnboardingProfile = $wp->getOption('woocommerce_onboarding_profile');

    if (
      is_array($wooCommerceOnboardingProfile)
      && isset($wooCommerceOnboardingProfile['business_extensions'])
      && is_array($wooCommerceOnboardingProfile['business_extensions'])
      && in_array('mailpoet', $wooCommerceOnboardingProfile['business_extensions'])
    ) {
      $installedViaWooCommerce = true;
    }

    return $installedViaWooCommerce;
  }
}

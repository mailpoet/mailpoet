<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Preview;

use MailPoet\WP\Functions as WPFunctions;

/**
 * Provides dummy WooCommerce data for email previews.
 * Uses WooCommerce filters when available, with fallback to default data.
 */
class WooCommerceDummyData {
  private WPFunctions $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  /**
   * Get a dummy WooCommerce order with placeholder data for preview.
   *
   * @return \WC_Order|null Dummy order or null if WooCommerce is unavailable
   */
  public function getOrder(): ?\WC_Order {
    if (!class_exists(\WC_Order::class)) {
      return null;
    }

    try {
      $order = new \WC_Order();
      $order->set_id(12345);

      $this->setOrderAddress($order);
      $this->addOrderItems($order);
      $this->setOrderMetadata($order);

      return $order;
    } catch (\Throwable $e) {
      return null;
    }
  }

  /**
   * Get a dummy WooCommerce customer with placeholder data for preview.
   *
   * @return \WC_Customer|null Dummy customer or null if WooCommerce is unavailable
   */
  public function getCustomer(): ?\WC_Customer {
    if (!class_exists(\WC_Customer::class)) {
      return null;
    }

    try {
      $address = $this->getAddress();
      $customer = new \WC_Customer();
      $customer->set_id(0);

      // Set basic info
      $customer->set_first_name($address['first_name']);
      $customer->set_last_name($address['last_name']);
      $customer->set_email($address['email']);

      // Set billing address
      $customer->set_billing_first_name($address['first_name']);
      $customer->set_billing_last_name($address['last_name']);
      $customer->set_billing_company($address['company']);
      $customer->set_billing_address_1($address['address_1']);
      $customer->set_billing_city($address['city']);
      $customer->set_billing_state($address['state']);
      $customer->set_billing_postcode($address['postcode']);
      $customer->set_billing_country($address['country']);
      $customer->set_billing_phone($address['phone']);
      $customer->set_billing_email($address['email']);

      // Set shipping address (same as billing)
      $customer->set_shipping_first_name($address['first_name']);
      $customer->set_shipping_last_name($address['last_name']);
      $customer->set_shipping_company($address['company']);
      $customer->set_shipping_address_1($address['address_1']);
      $customer->set_shipping_city($address['city']);
      $customer->set_shipping_state($address['state']);
      $customer->set_shipping_postcode($address['postcode']);
      $customer->set_shipping_country($address['country']);

      return $customer;
    } catch (\Throwable $e) {
      return null;
    }
  }

  /**
   * Get dummy address data using WooCommerce filter with fallback.
   *
   * @return array{first_name: string, last_name: string, company: string, email: string, phone: string, address_1: string, city: string, postcode: string, country: string, state: string}
   */
  private function getAddress(): array {
    $default = [
      'first_name' => 'John',
      'last_name' => 'Doe',
      'company' => 'Company',
      'email' => 'john@company.com',
      'phone' => '555-555-5555',
      'address_1' => '123 Fake Street',
      'city' => 'Faketown',
      'postcode' => '12345',
      'country' => 'US',
      'state' => 'CA',
    ];

    $address = $this->wp->applyFilters('woocommerce_email_preview_dummy_address', $default, null);

    if (!is_array($address)) {
      return $default;
    }

    return [
      'first_name' => (string)($address['first_name'] ?? $default['first_name']),
      'last_name' => (string)($address['last_name'] ?? $default['last_name']),
      'company' => (string)($address['company'] ?? $default['company']),
      'email' => (string)($address['email'] ?? $default['email']),
      'phone' => (string)($address['phone'] ?? $default['phone']),
      'address_1' => (string)($address['address_1'] ?? $default['address_1']),
      'city' => (string)($address['city'] ?? $default['city']),
      'postcode' => (string)($address['postcode'] ?? $default['postcode']),
      'country' => (string)($address['country'] ?? $default['country']),
      'state' => (string)($address['state'] ?? $default['state']),
    ];
  }

  /**
   * Set billing and shipping address on order.
   */
  private function setOrderAddress(\WC_Order $order): void {
    $address = $this->getAddress();

    // Billing
    $order->set_billing_first_name($address['first_name']);
    $order->set_billing_last_name($address['last_name']);
    $order->set_billing_company($address['company']);
    $order->set_billing_email($address['email']);
    $order->set_billing_phone($address['phone']);
    $order->set_billing_address_1($address['address_1']);
    $order->set_billing_city($address['city']);
    $order->set_billing_state($address['state']);
    $order->set_billing_postcode($address['postcode']);
    $order->set_billing_country($address['country']);

    // Shipping (same as billing)
    $order->set_shipping_first_name($address['first_name']);
    $order->set_shipping_last_name($address['last_name']);
    $order->set_shipping_company($address['company']);
    $order->set_shipping_address_1($address['address_1']);
    $order->set_shipping_city($address['city']);
    $order->set_shipping_state($address['state']);
    $order->set_shipping_postcode($address['postcode']);
    $order->set_shipping_country($address['country']);
  }

  /**
   * Set order metadata (status, totals, payment, etc.).
   */
  private function setOrderMetadata(\WC_Order $order): void {
    $order->set_status('completed');
    $order->set_currency(function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'USD');
    $order->set_total('99.99');
    $order->set_discount_total('10');
    $order->set_shipping_total('5');
    $order->set_date_created(time());
    $order->set_payment_method('bacs');
    $order->set_payment_method_title('Direct Bank Transfer');
  }

  /**
   * Add dummy line items to the order.
   * Uses WooCommerce filters when available, with fallback to default products.
   */
  private function addOrderItems(\WC_Order $order): void {
    if (!class_exists(\WC_Order_Item_Product::class)) {
      return;
    }

    $product = $this->getProduct();
    if ($product) {
      $item = new \WC_Order_Item_Product();
      $item->set_props([
        'name' => $product->get_name(),
        'product_id' => $product->get_id(),
        'quantity' => 2,
        'subtotal' => (string)((float)$product->get_price() * 2),
        'total' => (string)((float)$product->get_price() * 2),
      ]);
      $order->add_item($item);
    }

    $variation = $this->getProductVariation();
    if ($variation) {
      $item = new \WC_Order_Item_Product();
      $item->set_props([
        'name' => $variation->get_name(),
        'product_id' => $variation->get_parent_id(),
        'variation_id' => $variation->get_id(),
        'quantity' => 1,
        'subtotal' => $variation->get_price(),
        'total' => $variation->get_price(),
      ]);
      $order->add_item($item);
    }
  }

  /**
   * Get a dummy product using WooCommerce filter with fallback.
   */
  private function getProduct(): ?\WC_Product {
    if (!class_exists(\WC_Product::class)) {
      return null;
    }

    // Try to get from filter first
    $product = $this->wp->applyFilters('woocommerce_email_preview_dummy_product', null, null);

    if ($product instanceof \WC_Product) {
      return $product;
    }

    // Fallback: create default dummy product
    $product = new \WC_Product();
    $product->set_name(__('Dummy Product', 'woocommerce'));
    $product->set_price('25');

    return $product;
  }

  /**
   * Get a dummy product variation using WooCommerce filter with fallback.
   */
  private function getProductVariation(): ?\WC_Product_Variation {
    if (!class_exists(\WC_Product_Variation::class)) {
      return null;
    }

    // Try to get from filter first
    $variation = $this->wp->applyFilters('woocommerce_email_preview_dummy_product_variation', null, null);

    if ($variation instanceof \WC_Product_Variation) {
      return $variation;
    }

    // Fallback: create default dummy variation
    $variation = new \WC_Product_Variation();
    $variation->set_name(__('Dummy Product Variation', 'woocommerce'));
    $variation->set_price('20');

    return $variation;
  }
}

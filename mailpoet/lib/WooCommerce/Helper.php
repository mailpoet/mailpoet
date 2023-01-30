<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\WooCommerce;

use Automattic\WooCommerce\Admin\API\Reports\Customers\Stats\Query;
use MailPoet\DI\ContainerWrapper;
use MailPoet\RuntimeException;
use MailPoet\WP\Functions as WPFunctions;

class Helper {
  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function isWooCommerceActive() {
    return class_exists('WooCommerce');
  }

  public function getWooCommerceVersion() {
    return $this->isWooCommerceActive() ? get_plugin_data(WP_PLUGIN_DIR . '/woocommerce/woocommerce.php')['Version'] : null;
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

  public function wcGetPageId(string $page): ?int {
    if ($this->isWooCommerceActive()) {
      return (int)wc_get_page_id($page);
    }
    return null;
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

  public function getCustomersCount(): int {
    if (!class_exists(Query::class)) {
      return 0;
    }
    $query = new Query([
      'fields' => ['customers_count'],
    ]);
    // Query::get_data declares it returns array but the underlying DataStore returns stdClass
    $result = (array)$query->get_data();
    return isset($result['customers_count']) ? intval($result['customers_count']) : 0;
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

  public function getOrdersTableName() {
    if (!method_exists('\Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore', 'get_orders_table_name')) {
      throw new RuntimeException('Cannot get orders table name when running a WooCommerce version that doesn\'t support custom order tables.');
    }

    return \Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore::get_orders_table_name();
  }

  public function getAddressesTableName() {
    if (!method_exists('\Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore', 'get_addresses_table_name')) {
      throw new RuntimeException('Cannot get addresses table name when running a WooCommerce version that doesn\'t support custom order tables.');
    }

    return \Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore::get_addresses_table_name();
  }

  public function wcGetCouponTypes(): array {
    return wc_get_coupon_types();
  }

  public function wcGetCouponCodeById(int $id): string {
    return wc_get_coupon_code_by_id($id);
  }

  /**
   * @param mixed $data Coupon data, object, ID or code.
   */
  public function createWcCoupon($data) {
    return new \WC_Coupon($data);
  }

  public function getCouponList(): array {
    $couponPosts = $this->wp->getPosts([
      'posts_per_page' => -1,
      'orderby' => 'name',
      'order' => 'asc',
      'post_type' => 'shop_coupon',
      'post_status' => 'publish',
    ]);

    return array_map(function(\WP_Post $post): array {
      return [
        'id' => $post->ID,
        'text' => $post->post_title, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      ];
    }, $couponPosts);
  }

  public function wcGetPriceDecimalSeparator() {
    return wc_get_price_decimal_separator();
  }
}

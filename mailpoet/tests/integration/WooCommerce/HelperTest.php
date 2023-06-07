<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

use MailPoet\WP\Functions as WPFunctions;

/**
 * @group woo
 */
class HelperTest extends \MailPoetTest {
  /** @var WPFunctions */
  private $wp;

  /** @var Helper */
  private $helper;

  public function _before() {
    parent::_before();

    $this->wp = $this->diContainer->get(WPFunctions::class);
    $this->helper = $this->diContainer->get(Helper::class);
  }

  public function _after() {
    global $wpdb;

    parent::_after();
    $this->wp->deleteOption('woocommerce_onboarding_profile');
    $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}woocommerce_shipping_zone_methods");
    $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}woocommerce_shipping_zone_locations");
    $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}woocommerce_shipping_zones");
  }

  public function testGetDataMailPoetNotInstalledViaWooCommerceOnboardingWizard() {
    $this->assertFalse($this->helper->wasMailPoetInstalledViaWooCommerceOnboardingWizard());

    $this->wp->updateOption('woocommerce_onboarding_profile', ['business_extensions' => ['jetpack', 'mailchimp', 'another_plugin']]);
    $this->assertFalse($this->helper->wasMailPoetInstalledViaWooCommerceOnboardingWizard());
  }

  public function testGetDataMailPoetInstalledViaWooCommerceOnboardingWizard() {
    $this->wp->updateOption('woocommerce_onboarding_profile', ['business_extensions' => ['jetpack', 'mailchimp', 'mailpoet', 'another_plugin']]);
    $this->assertTrue($this->helper->wasMailPoetInstalledViaWooCommerceOnboardingWizard());
  }

  public function testGetOrdersCountCreatedBefore() {
    $this->tester->createWooCommerceOrder(['date_created' => '2022-07-01 00:00:00']);
    $this->tester->createWooCommerceOrder(['date_created' => '2022-07-31 23:59:59']);
    $this->tester->createWooCommerceOrder(['date_created' => '2022-08-01 00:00:00']);

    $this->assertSame(2, $this->helper->getOrdersCountCreatedBefore('2022-08-01 00:00:00'));
  }

  public function testGetShippingMethodInstances() {
    $this->createShippingZonesWithShippingMethods();

    $expectedResult = [
      [
        'id' => 'flat_rate:2',
        'name' => 'Flat rate custom name (Argentina)',
      ],
      [
        'id' => 'local_pickup:3',
        'name' => 'Local pickup custom name (Argentina)',
      ],
      [
        'id' => 'free_shipping:4',
        'name' => 'Free shipping custom name (Brazil)',
      ],
      [
        'id' => 'flat_rate:1',
        'name' => 'Flat rate custom name (Locations not covered by your other zones)',
      ],
    ];

    $this->assertEquals($expectedResult, $this->helper->getShippingMethodInstancesData());
  }

  protected function createShippingZonesWithShippingMethods() {
    $outOfCoverageShippingZone = new \WC_Shipping_Zone(0);
    $outOfCoverageShippingZone->save();

    $this->addShippingMethodToZone($outOfCoverageShippingZone, 'flat_rate', [
      'woocommerce_flat_rate_title' => 'Flat rate custom name',
      'woocommerce_flat_rate_tax_status' => 'none',
      'woocommerce_flat_rate_cost' => '100',
    ]);

    $shippingZoneArgentina = $this->createShippingZone('Argentina', 'AR');

    $this->addShippingMethodToZone($shippingZoneArgentina, 'flat_rate', [
      'woocommerce_flat_rate_title' => 'Flat rate custom name',
      'woocommerce_flat_rate_tax_status' => 'none',
      'woocommerce_flat_rate_cost' => '15',
    ]);

    $this->addShippingMethodToZone($shippingZoneArgentina, 'local_pickup', [
      'woocommerce_local_pickup_title' => 'Local pickup custom name',
      'woocommerce_local_pickup_tax_status' => 'taxable',
      'woocommerce_local_pickup_cost' => '10',
    ]);

    $shippingZoneBrazil = $this->createShippingZone('Brazil', 'BR');

    $this->addShippingMethodToZone($shippingZoneBrazil, 'free_shipping', [
      'woocommerce_free_shipping_title' => 'Free shipping custom name',
      'woocommerce_free_shipping_requires' => '',
      'woocommerce_free_shipping_min_amount' => '0',
    ]);
  }

  protected function createShippingZone($zoneName, $countryName): \WC_Shipping_Zone {
    $shippingZone = new \WC_Shipping_Zone();
    $shippingZone->set_zone_name($zoneName);
    $shippingZone->add_location($countryName, 'country');
    $shippingZone->save();

    return $shippingZone;
  }

  protected function addShippingMethodToZone($shippingZone, $shippingMethodType, $shippingMethodData) {
    $instanceId = $shippingZone->add_shipping_method($shippingMethodType);
    $shippingMethodData['instance_id'] = $instanceId;

    $shippingMethod = \WC_Shipping_Zones::get_shipping_method($instanceId);
    $this->assertInstanceOf(\WC_Shipping_Method::class, $shippingMethod);
    $shippingMethod->set_post_data($shippingMethodData);
    $_REQUEST['instance_id'] = $instanceId; // workaround to make process_admin_options() save the instance data
    $shippingMethod->process_admin_options();
  }
}

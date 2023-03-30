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
    parent::_after();
    $this->wp->deleteOption('woocommerce_onboarding_profile');
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
}

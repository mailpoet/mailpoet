<?php

namespace MailPoet\WooCommerce;

use MailPoet\WP\Functions as WPFunctions;

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
    $this->wp->deleteOption('woocommerce_onboarding_profile');
  }

  public function testGetDataMailPoetNotInstalledViaWooCommerceOnboardingWizard() {
    $this->assertFalse($this->helper->wasMailPoetInstalledViaWooCommerceOnboardingWizard());

    $this->wp->addOption('woocommerce_onboarding_profile', ['business_extensions' => ['jetpack', 'mailchimp', 'another_plugin']]);
    $this->assertFalse($this->helper->wasMailPoetInstalledViaWooCommerceOnboardingWizard());
  }

  public function testGetDataMailPoetInstalledViaWooCommerceOnboardingWizard() {
    $this->wp->addOption('woocommerce_onboarding_profile', ['business_extensions' => ['jetpack', 'mailchimp', 'mailpoet', 'another_plugin']]);
    $this->assertTrue($this->helper->wasMailPoetInstalledViaWooCommerceOnboardingWizard());
  }
}

<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Services\Bridge;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Settings;

class HideAutomationsForBundlesAndAutomateWooCest {
  public function dontSeeWooCommerceTabForBundleAndAutomateWoo(\AcceptanceTester $i) {
    $i->wantTo('do not see WooCommerce Tab for bundles when AutomateWoo and WooCommerce are active');
    $newsletterFactory = new Newsletter();
    $newsletterFactory
      ->withSubject('Testing AutomateWoo Automations for bundles')
      ->create();
    $i->activateWooCommerce();
    $i->activateAutomateWoo();
    $settings = new Settings();
    $settings->withValidMssKey('apiKey');
    $settings->withSubscriptionType(Bridge::WPCOM_BUNDLE_SUBSCRIPTION_TYPE);
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->dontSeeElement('[data-automation-id="tab-WooCommerce"]');
  }

  public function seeAutomateWooAutomationsForBundleAndAutomateWoo(\AcceptanceTester $i) {
    $i->wantTo('see AutomateWoo Automations for bundles when AutomateWoo is active');
    $i->activateWooCommerce();
    $i->activateAutomateWoo();
    $settings = new Settings();
    $settings->withValidMssKey('apiKey');
    $settings->withSubscriptionType(Bridge::WPCOM_BUNDLE_SUBSCRIPTION_TYPE);
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->wantTo('Not see WooCommerce Automatic emails');
    $i->dontSeeElement('[data-automation-id="create_woocommerce_product_purchased_in_category"]');
    $i->wantTo('See WooCommerce Email Customizer');
    $i->seeElement('[data-automation-id="customize_woocommerce"]');
    $i->wantTo('See AutomateWoo Automations');
    $i->seeElement('[data-automation-id="woocommerce_automatewoo"]');
    $i->click('[data-automation-id="woocommerce_automatewoo"]');
    $i->seeElement('#automatewoo-workflow-tabs-root');
    $i->deactivateAutomateWoo();
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->wantTo('See WooCommerce Automatic emails');
    $i->seeElement('[data-automation-id="create_woocommerce_product_purchased_in_category"]');
    $i->wantTo('Not see AutomateWoo Automations');
    $i->dontSeeElement('[data-automation-id="woocommerce_automatewoo"]');
  }
}

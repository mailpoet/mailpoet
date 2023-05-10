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
}

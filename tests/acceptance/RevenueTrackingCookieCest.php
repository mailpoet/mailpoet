<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Features\FeaturesController;
use MailPoet\Test\DataFactories\Features;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Settings;

require_once __DIR__ . '/../DataFactories/Features.php';
require_once __DIR__ . '/../DataFactories/Newsletter.php';
require_once __DIR__ . '/../DataFactories/Settings.php';

class RevenueTrackingCookieCest {

  /** @var Settings */
  private $settings;

  /** @var Features */
  private $features;

  protected function _inject(Settings $settings, Features $features) {
    $this->settings = $settings;
    $this->features = $features;
  }

  function _before(\AcceptanceTester $I) {
    $I->activateWooCommerce();
    $this->features->withFeatureEnabled(FeaturesController::FEATURE_DISPLAY_WOOCOMMERCE_REVENUES);
  }

  function _after(\AcceptanceTester $I) {
    $I->deactivateWooCommerce();
  }

  function cookieIsStoredOnClick(\AcceptanceTester $I) {
    $I->wantTo('Test Revenue cookie is saved');
    $newsletter_subject = 'Receive Test';
    $newsletter = (new Newsletter())->withSubject($newsletter_subject)->create();
    // make sure the settings is disabled
    $this->settings->withTrackingEnabled()->withCookieRevenueTrackingDisabled();
    $segment_name = $I->createListWithSubscriber();

    $I->login();
    // enable the settings
    $I->amOnMailPoetPage('Settings');
    $I->click('[data-automation-id="woocommerce_settings_tab"]');
    $I->dontSeeCheckboxIsChecked('[data-automation-id="accept_cookie_revenue_tracking"]');
    $I->checkOption('[data-automation-id="accept_cookie_revenue_tracking"]');
    $I->click('[data-automation-id="settings-submit-button"]');
    $I->waitForText('Settings saved');

    // send any newsletter with a link
    $I->amEditingNewsletter($newsletter->id);
    $I->click('Next');

    $I->waitForElement('[data-automation-id="newsletter_send_form"]');
    $I->selectOptionInSelect2($segment_name);
    $I->click('Send');
    $I->waitForElement('.mailpoet_progress_label');

    $I->logOut();
    $I->amOnMailboxAppPage();
    $I->waitForText($newsletter_subject);
    // click a link in the newsletter and check the cookie has been created
    $I->click(Locator::contains('span.subject', $newsletter_subject));
    $I->switchToIframe('preview-html');
    $I->click('Read the post');
    $I->switchToNextTab();
    $I->canSeeCookie('mailpoet_revenue_tracking');
  }

  function cookieIsNotStoredWhenSettingsDisabled(\AcceptanceTester $I) {
    $I->wantTo('Test Revenue cookie is saved');
    $newsletter_subject = 'Receive Test';
    $newsletter = (new Newsletter())->withSubject($newsletter_subject)->create();
    // make sure the settings is enabled
    $this->settings->withTrackingEnabled()->withCookieRevenueTracking();
    $segment_name = $I->createListWithSubscriber();

    $I->login();
    // dis the settings
    $I->amOnMailPoetPage('Settings');
    $I->click('[data-automation-id="woocommerce_settings_tab"]');
    $I->seeCheckboxIsChecked('[data-automation-id="accept_cookie_revenue_tracking"]');
    $I->uncheckOption('[data-automation-id="accept_cookie_revenue_tracking"]');
    $I->click('[data-automation-id="settings-submit-button"]');
    $I->waitForText('Settings saved');
    // send any newsletter with a link
    $I->amEditingNewsletter($newsletter->id);
    $I->click('Next');

    $I->waitForElement('[data-automation-id="newsletter_send_form"]');
    $I->selectOptionInSelect2($segment_name);
    $I->click('Send');
    $I->waitForElement('.mailpoet_progress_label');

    $I->logOut();
    $I->amOnMailboxAppPage();
    $I->waitForText($newsletter_subject);
    // click a link in the newsletter and check the cookie has NOT been created
    $I->click(Locator::contains('span.subject', $newsletter_subject));
    $I->switchToIframe('preview-html');
    $I->click('Read the post');
    $I->switchToNextTab();
    $I->dontSeeCookie('mailpoet_revenue_tracking');
  }

}

<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Settings;

/**
 * @group woo
 */
class RevenueTrackingCookieCest {

  /** @var Settings */
  private $settings;

  public function _before(\AcceptanceTester $i) {
    $this->settings = new Settings();
    $i->activateWooCommerce();
    $this->settings->withCronTriggerMethod('Action Scheduler');
  }

  public function cookieIsStoredOnClick(\AcceptanceTester $i) {
    $i->wantTo('Test Revenue cookie is saved');
    $newsletterSubject = 'Receive Test' . \MailPoet\Util\Security::generateRandomString();
    $newsletter = (new Newsletter())->withSubject($newsletterSubject)->create();
    // make sure the settings is disabled
    $this->settings->withTrackingEnabled()->withCookieRevenueTrackingDisabled();
    $segmentName = $i->createListWithSubscriber();
    // make sure a post exists
    $i->cli(['post', 'create', '--post_status=publish', '--post_type=post', '--post_title=Lorem', '--post_content=Ipsum']);

    $i->login();
    // enable the settings
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="settings-advanced-tab"]');
    $i->dontSeeCheckboxIsChecked('[data-automation-id="tracking-full-radio"] input');
    $i->click('[data-automation-id="tracking-full-radio"]');
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->waitForText('Settings saved');

    // send any newsletter with a link
    $i->amEditingNewsletter($newsletter->getId());
    $i->click('Next');

    $i->waitForElement('[data-automation-id="newsletter_send_form"]');
    $i->selectOptionInSelect2($segmentName);
    $i->click('Send');
    $i->waitForEmailSendingOrSent();

    $i->triggerMailPoetActionScheduler();

    $i->logOut();
    $i->checkEmailWasReceived($newsletterSubject);

    // click a link in the newsletter and check the cookie has been created
    $i->click(Locator::contains('span.subject', $newsletterSubject));
    $i->switchToIframe('#preview-html');
    $i->click('Read the post');
    $i->switchToNextTab();
    $i->canSeeCookie('mailpoet_revenue_tracking');
  }

  public function cookieIsNotStoredWhenSettingsDisabled(\AcceptanceTester $i) {
    $i->wantTo('Test Revenue cookie is not saved');
    $newsletterSubject = 'Receive Test' . \MailPoet\Util\Security::generateRandomString();
    $newsletter = (new Newsletter())->withSubject($newsletterSubject)->create();
    // make sure the settings is enabled
    $this->settings->withTrackingEnabled()->withCookieRevenueTracking();
    $segmentName = $i->createListWithSubscriber();
    // make sure a post exists
    $i->cli(['post', 'create', '--post_status=publish', '--post_type=post', '--post_title=Lorem', '--post_content=Ipsum']);

    $i->login();
    // dis the settings
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="settings-advanced-tab"]');
    $i->seeCheckboxIsChecked('[data-automation-id="tracking-full-radio"] input');
    $i->click('[data-automation-id="tracking-basic-radio"]');
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->waitForText('Settings saved');
    // send any newsletter with a link
    $i->amEditingNewsletter($newsletter->getId());
    $i->click('Next');

    $i->waitForElement('[data-automation-id="newsletter_send_form"]');
    $i->selectOptionInSelect2($segmentName);
    $i->click('Send');
    $i->waitForEmailSendingOrSent();

    $i->triggerMailPoetActionScheduler();

    $i->logOut();
    $i->checkEmailWasReceived($newsletterSubject);
    // click a link in the newsletter and check the cookie has NOT been created
    $i->click(Locator::contains('span.subject', $newsletterSubject));
    $i->switchToIframe('#preview-html');
    $i->click('Read the post');
    $i->switchToNextTab();
    $i->dontSeeCookie('mailpoet_revenue_tracking');
  }
}

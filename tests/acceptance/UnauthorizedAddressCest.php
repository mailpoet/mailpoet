<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Mailer\Mailer;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Settings;

require_once __DIR__ . '/../DataFactories/Newsletter.php';
require_once __DIR__ . '/../DataFactories/Settings.php';

class UnauthorizedAddressCest {
  function _before() {
    $settings = new Settings();
    $settings->withSendingMethodMailPoet();
  }

  function unauthorizedFreeAddressError(\AcceptanceTester $I) {
    $I->wantTo('See proper sending error when using unauthorized free address');

    $newsletter_title = 'Unauthorized free address';
    $newsletter_from = 'random@gmail.com';
    $segment_name = $I->createListWithSubscriber();

    // step 1 - Prepare newsletter data
    $newsletterFactory = new Newsletter();
    $newsletter = $newsletterFactory
      ->withSubject($newsletter_title)
      ->loadBodyFrom('newsletterWithText.json')
      ->create();

    // step 2 - Go to editor
    $I->login();
    $I->amEditingNewsletter($newsletter->id);
    $I->click('Next');

    // step 3 - Choose list and send
    $I->waitForElement('[data-automation-id="newsletter_send_form"]');
    $I->selectOptionInSelect2($segment_name);
    $I->fillField('[name="sender_address"]', $newsletter_from);
    $I->click('Send');

    // step 4 - Check that error notice is visible
    $I->waitForElement('[data-automation-id="newsletters_listing_tabs"]');
    $I->wait(2);
    $I->amOnMailpoetPage('Emails');
    $I->waitForElement('.mailpoet_notice.notice-error');
    $I->see('The MailPoet Sending Service did not send your latest email because the address ' . $newsletter_from, '.notice-error');
    $href = $I->grabAttributeFrom('//a[text()="Authorize your email in your account now."]', 'href');
    expect($href)->equals('https://account.mailpoet.com/authorization');

    // step 5 - Trash newsletter and resume sending
    $I->clickItemRowActionByItemName($newsletter_title, 'Move to trash');
    $I->scrollToTop();
    $I->click('Resume sending');
    $I->waitForText('Sending has been resumed.');
  }

  function unauthorizedOwnAddressError(\AcceptanceTester $I) {
    $I->wantTo('See proper sending error when using unauthorized own address');

    $newsletter_title = 'Unauthorized own address';
    $newsletter_from = 'random@own-domain.com';
    $segment_name = $I->createListWithSubscriber();

    // step 1 - Prepare newsletter data
    $newsletterFactory = new Newsletter();
    $newsletter = $newsletterFactory
      ->withSubject($newsletter_title)
      ->loadBodyFrom('newsletterWithText.json')
      ->create();

    // step 2 - Go to editor
    $I->login();
    $I->amEditingNewsletter($newsletter->id);
    $I->click('Next');

    // step 3 - Choose list and send
    $I->waitForElement('[data-automation-id="newsletter_send_form"]');
    $I->selectOptionInSelect2($segment_name);
    $I->fillField('[name="sender_address"]', $newsletter_from);
    $I->click('Send');

    // step 4 - Check that error notice is visible
    $I->waitForElement('[data-automation-id="newsletters_listing_tabs"]');
    $I->wait(2);
    $I->amOnMailpoetPage('Emails');
    $I->waitForElement('.mailpoet_notice.notice-error');
    $I->see('The MailPoet Sending Service did not send your latest email because the address ' . $newsletter_from, '.notice-error');
    $href = $I->grabAttributeFrom('//a[text()="Authorize your email in your account now."]', 'href');
    expect($href)->equals('https://account.mailpoet.com/authorization');

    // step 5 - Trash newsletter and resume sending
    $I->clickItemRowActionByItemName($newsletter_title, 'Move to trash');
    $I->scrollToTop();
    $I->click('Resume sending');
    $I->waitForText('Sending has been resumed.');
  }

  function _after() {
    $settings = new Settings();
    $settings->withSendingMethodSmtpMailhog();
  }
}

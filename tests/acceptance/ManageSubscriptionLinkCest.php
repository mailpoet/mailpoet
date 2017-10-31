<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;

class ManageSubscriptionLinkCest {
  function __construct() {
    $this->newsletter_title = 'Subscription links Email ' . \MailPoet\Util\Security::generateRandomString();
  }

  function sendEmail(\AcceptanceTester $I) {
    $I->wantTo('Create and send new email to WordPress Users list');

    $I->loginAsAdmin();
    $I->amOnMailpoetPage('Emails');
    $I->click('a.page-title-action');

    // step 1 - select type
    $I->seeInCurrentUrl('#/new');
    $I->click('Create');
    $I->wait(3);

    // step 2 - select template
    $I->seeInCurrentUrl('#/template');
    $I->click('Select', 'ul.mailpoet_boxes > li:nth-child(1)');
    $I->wait(3);

    // step 3 - design newsletter (update subject)
    $I->seeInCurrentUrl('mailpoet-newsletter-editor');
    $I->fillField('.mailpoet_input_title', $this->newsletter_title);
    $I->click('Next');

    // step 4 - send
    $I->waitForElement('input.select2-search__field');
    $I->seeInCurrentUrl('#/send');
    $I->fillField('input.select2-search__field', 'WordPress Users');
    $I->pressKey('input.select2-search__field', \WebDriverKeys::ENTER);
    $I->click('Send');
    $I->wait(3);
    $I->waitForText('Sent to 1 of 1');
  }

  function manageSubscriptionLink(\AcceptanceTester $I) {
    $I->wantTo('Verify that "manage subscription" link works and subscriber status can be updated');

    $I->amOnUrl('http://mailhog:8025');
    $I->click(Locator::contains('span.subject', $this->newsletter_title));
    $I->switchToIframe('preview-html');
    $I->click('Manage subscription');
    $I->switchToNextTab();
    $I->waitForText('Manage your subscription');

    // set status to unsubscribed
    $I->selectOption('.mailpoet_select', 'Unsubscribed');
    $I->click('Save');
    $I->wait(3);
    $I->seeOptionIsSelected('.mailpoet_select', 'Unsubscribed');

    // change status back to subscribed
    $I->selectOption('.mailpoet_select', 'Subscribed');
    $I->click('Save');
    $I->wait(3);
    $I->seeOptionIsSelected('.mailpoet_select', 'Subscribed');
    $I->seeNoJSErrors();
  }

  function unsubscribeLink(\AcceptanceTester $I) {
    $I->wantTo('Verify that "unsubscribe" link works and subscriber status is set to unsubscribed');

    $I->amOnUrl('http://mailhog:8025');
    $I->click(Locator::contains('span.subject', $this->newsletter_title));
    $I->switchToIframe('preview-html');
    $I->click('Unsubscribe');
    $I->switchToNextTab();
    $I->waitForText('You are now unsubscribed');
    $I->click('Manage your subscription');
    $I->wait(3);
    $I->seeOptionIsSelected('.mailpoet_select', 'Unsubscribed');
    $I->seeNoJSErrors();
  }
}
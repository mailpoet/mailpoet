<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Test\DataFactories\Settings;

require_once __DIR__ . '/../DataFactories/Settings.php';

class ManageSubscriptionLinkCest {

  function __construct() {
    $this->newsletter_title = 'Subscription links Email ' . \MailPoet\Util\Security::generateRandomString();
  }

  function _before() {
    $settings = new Settings();
    $settings->withConfirmationEmailEnabled();
  }

  function sendEmail(\AcceptanceTester $I) {
    $I->wantTo('Create and send new email to WordPress Users list');
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id="new_email"]');

    // step 1 - select type
    $I->click('[data-automation-id="create_standard"]');

    // step 2 - select template
    $first_template_element = '[data-automation-id="select_template_0"]';
    $I->waitForElement($first_template_element);
    $I->click($first_template_element);

    // step 3 - design newsletter (update subject)
    $title_element ='[data-automation-id="newsletter_title"]';
    $I->waitForElement($title_element);
    $I->fillField($title_element, $this->newsletter_title);
    $I->click('Next');

    // step 4 - send
    $search_field_element = 'input.select2-search__field';
    $I->waitForElement($search_field_element);
    $I->selectOptionInSelect2('WordPress Users');
    $I->click('Send');
    $I->waitForText('Sent to 1 of 1', 60);
  }

  function manageSubscriptionLink(\AcceptanceTester $I) {
    $I->wantTo('Verify that "manage subscription" link works and subscriber status can be updated');
    $I->amOnMailboxAppPage();
    $I->click(Locator::contains('span.subject', $this->newsletter_title));
    $I->switchToIframe('preview-html');
    $I->waitForElementChange(
        \Codeception\Util\Locator::contains('a', 'Manage your subscription'), function ($el) {
            return $el->getAttribute('target') === "_blank";
        }, 100
    );
    $I->click('Manage your subscription');
    $I->switchToNextTab();
    $I->waitForText('Manage your subscription');

    $form_status_element = '[data-automation-id="form_status"]';

    // set status to unsubscribed
    $I->selectOption($form_status_element, 'Unsubscribed');
    $I->click('Save');
    $I->waitForElement($form_status_element);
    $I->seeOptionIsSelected($form_status_element, 'Unsubscribed');

    // change status back to subscribed
    $I->selectOption($form_status_element, 'Subscribed');
    $I->click('Save');
    $I->waitForElement($form_status_element);
    $I->seeOptionIsSelected($form_status_element, 'Subscribed');
    $I->seeNoJSErrors();
  }

  function unsubscribeLink(\AcceptanceTester $I) {
    $I->wantTo('Verify that "unsubscribe" link works and subscriber status is set to unsubscribed');

    $form_status_element = '[data-automation-id="form_status"]';

    $I->amOnMailboxAppPage();
    $I->click(Locator::contains('span.subject', $this->newsletter_title));
    $I->switchToIframe('preview-html');
    $I->waitForElementChange(
        \Codeception\Util\Locator::contains('a', 'Unsubscribe'), function ($el) {
            return $el->getAttribute('target') === "_blank";
        }, 100
    );
    $I->click('Unsubscribe');
    $I->switchToNextTab();
    $I->waitForText('You are now unsubscribed');
    $I->click('Manage your subscription');
    $I->seeOptionIsSelected($form_status_element, 'Unsubscribed');
    $I->seeNoJSErrors();
  }
}

<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Test\DataFactories\Settings;

class ManageSubscriptionLinkCest {

  /** @var Settings */
  private $settings;

  /** @var string */
  private $newsletter_title;

  public function __construct() {
    $this->newsletter_title = 'Subscription links Email ' . \MailPoet\Util\Security::generateRandomString();
  }

  protected function _inject(Settings $settings) {
    $this->settings = $settings;
  }

  public function _before() {
    $this->settings
      ->withConfirmationEmailEnabled()
      ->withCronTriggerMethod('WordPress');
  }

  public function manageSubscriptionLink(\AcceptanceTester $I) {
    $I->wantTo('Verify that "manage subscription" link works and subscriber status can be updated');
    $this->sendEmail($I);
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
    $approximate_save_button_height = 50; // Used for scroll offset to ensure that button is not hidden above the top fold
    $I->selectOption($form_status_element, 'Unsubscribed');
    $I->scrollTo('[data-automation-id="subscribe-submit-button"]', 0, -$approximate_save_button_height);
    $I->click('Save');
    $I->waitForElement($form_status_element);
    $I->seeOptionIsSelected($form_status_element, 'Unsubscribed');

    // change status back to subscribed
    $I->selectOption($form_status_element, 'Subscribed');
    $I->scrollTo('[data-automation-id="subscribe-submit-button"]', 0, -$approximate_save_button_height);
    $I->click('Save');
    $I->waitForElement($form_status_element);
    $I->seeOptionIsSelected($form_status_element, 'Subscribed');
    $I->seeNoJSErrors();
  }

  public function unsubscribeLink(\AcceptanceTester $I) {
    $I->wantTo('Verify that "unsubscribe" link works and subscriber status is set to unsubscribed');
    $this->sendEmail($I);

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

  private function sendEmail(\AcceptanceTester $I) {
    $segment_name = $I->createListWithSubscriber();

    $I->login();
    $I->amOnMailpoetPage('Emails');

    // step 1 - select type
    $I->click('[data-automation-id="create_standard"]');

    // step 2 - select template
    $first_template_element = '[data-automation-id="select_template_0"]';
    $I->waitForElement($first_template_element);
    $I->click($first_template_element);

    // step 3 - design newsletter (update subject)
    $title_element = '[data-automation-id="newsletter_title"]';
    $I->waitForElement($title_element);
    $I->fillField($title_element, $this->newsletter_title);
    $I->click('Next');

    // step 4 - send
    $search_field_element = 'input.select2-search__field';
    $I->waitForElement($search_field_element);
    $I->selectOptionInSelect2($segment_name);
    $I->click('Send');

    // Reloading page is faster than waiting for regular AJAX request to refresh it
    for ($i = 0; $i < 15; $i++) {
      try {
        $I->wait(2);
        $I->reloadPage();
        $I->waitForListingItemsToLoad();
        $I->see('Sent to 1 of 1');
        return;
      } catch (\PHPUnit_Framework_Exception $e) {
        continue;
      }
    }
    $I->see('Sent to 1 of 1');
  }
}

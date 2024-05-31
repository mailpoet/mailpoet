<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Settings;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Exception;

/**
 * @group frontend
 */
class ManageSubscriptionLinkCest {

  const ADDITIONAL_FIRST_FANCY_LIST = 'First fancy list';
  const ADDITIONAL_SECOND_FANCY_LIST = 'Second fancy list';

  /** @var Settings */
  private $settings;

  /** @var string */
  private $newsletterTitle;

  public function __construct() {
    $this->newsletterTitle = 'Subscription links Email ' . \MailPoet\Util\Security::generateRandomString();
  }

  public function _before() {
    $this->settings = new Settings();
    $this->settings
      ->withConfirmationEmailEnabled()
      ->withCronTriggerMethod('Action Scheduler');
    
    $segmentFactory = new Segment();
    $segmentFactory->withName(self::ADDITIONAL_FIRST_FANCY_LIST)->create();
    $segmentFactory->withName(self::ADDITIONAL_SECOND_FANCY_LIST)->create();
  }

  public function manageSubscriptionLink(\AcceptanceTester $i) {
    $i->wantTo('Verify that "manage subscription" link works and subscriber status and list can be updated');

    $this->sendEmail($i);

    $i->amOnMailboxAppPage();
    $i->click(Locator::contains('span.subject', $this->newsletterTitle));
    $i->switchToIframe('#preview-html');
    $i->waitForElementChange(
      \Codeception\Util\Locator::contains('a', 'Manage your subscription'),
      function ($el) {
            return $el->getAttribute('target') === "_blank";
      },
      100
    );
    $i->click('Manage your subscription');
    $i->switchToNextTab();
    $i->waitForText('Manage your subscription');
    $successMessage = 'Your preferences have been saved.';
    $i->dontSee($successMessage);

    $formStatusElement = '[data-automation-id="form_status"]';

    $i->wantTo('Set the status to unsubscribed');
    $approximateSaveButtonHeight = 50; // Used for scroll offset to ensure that button is not hidden above the top fold
    $i->selectOption($formStatusElement, 'Unsubscribed');
    $i->scrollTo('[data-automation-id="subscribe-submit-button"]', 0, -$approximateSaveButtonHeight);
    $i->click('Save');
    $i->waitForElement($formStatusElement);
    $i->seeOptionIsSelected($formStatusElement, 'Unsubscribed');
    $i->see($successMessage);

    $i->wantTo('Change the status to back to subscribed');
    $i->selectOption($formStatusElement, 'Subscribed');
    $i->scrollTo('[data-automation-id="subscribe-submit-button"]', 0, -$approximateSaveButtonHeight);
    $i->click('Save');
    $i->waitForElement($formStatusElement);
    $i->seeOptionIsSelected($formStatusElement, 'Subscribed');
    $i->seeNoJSErrors();

    $i->wantTo('Subscribe to the other two lists');
    $i->checkOption(self::ADDITIONAL_FIRST_FANCY_LIST);
    $i->checkOption(self::ADDITIONAL_SECOND_FANCY_LIST);
    $i->click('Save');
    $i->waitForElement($formStatusElement);
    $i->canSeeCheckboxIsChecked(self::ADDITIONAL_FIRST_FANCY_LIST);
    $i->canSeeCheckboxIsChecked(self::ADDITIONAL_SECOND_FANCY_LIST);
  }

  public function unsubscribeLinksWithLinkTracking(\AcceptanceTester $i) {
    $i->wantTo('Verify that "unsubscribe" links works with tracking enabled');
    $this->settings->withTrackingEnabled();
    $this->verifyUnsubscribeLinks($i);
  }

  public function unsubscribeLinksWithoutLinkTracking(\AcceptanceTester $i) {
    $i->wantTo('Verify that "unsubscribe" links works with tracking disabled');
    $this->settings->withTrackingDisabled();
    $this->verifyUnsubscribeLinks($i);
  }

  public function unsubscribeUrlWithoutToken(\AcceptanceTester $i) {
    $i->wantTo('Check if as a logged in user cannot unsubscribe using url without token');
    $i->login();
    // Make sure the admin is subscribed
    $i->amOnMailpoetPage('Subscribers');
    $i->waitForText(\AcceptanceTester::ADMIN_EMAIL);
    $i->clickItemRowActionByItemName(\AcceptanceTester::ADMIN_EMAIL, 'Edit');
    $i->waitForText('Subscriber');
    $i->waitForElement('[data-automation-id="subscriber_edit_form"]');
    $i->selectOption('[data-automation-id="subscriber-status"]', 'Subscribed');
    $i->click('Save');
    $i->waitForText('Subscriber was updated successfully!');
    $i->amOnUrl(\AcceptanceTester::WP_URL . '/?mailpoet_page=subscriptions&mailpoet_router&endpoint=subscription&action=unsubscribe&data=');
    $i->see("we don’t have a record of you.");
    $i->amOnMailpoetPage('Subscribers');
    $i->waitForText(\AcceptanceTester::ADMIN_EMAIL);
    $i->see('Subscribed');
  }

  private function verifyUnsubscribeLinks(\AcceptanceTester $i) {
    $this->sendEmail($i);
    $formStatusElement = '[data-automation-id="form_status"]';
    $i->wantTo('Verify that "Unsubscribe List" header link works and subscriber status is set to unsubscribed instantly');
    $i->amOnMailboxAppPage();
    $i->click(Locator::contains('span.subject', $this->newsletterTitle));
    $i->click('#show-headers');
    $i->waitForText('List-Unsubscribe');
    $link = $i->grabTextFrom('//div[@class="row headers"]//th[text()="List-Unsubscribe"]/following-sibling::td');
    Assert::assertIsString($link);
    $link = trim($link, '<>');
    $i->amOnUrl($link);
    $i->waitForText('You are now unsubscribed', 15);
    $i->click('Manage your subscription');
    $i->seeOptionIsSelected($formStatusElement, 'Unsubscribed');

    // Re-subscribe to test the link in newsletter body
    $i->selectOption($formStatusElement, 'Subscribed');
    $approximateSaveButtonHeight = 50; // Used for scroll offset to ensure that button is not hidden above the top fold
    $i->scrollTo('[data-automation-id="subscribe-submit-button"]', 0, -$approximateSaveButtonHeight);
    $i->click('Save');
    $i->waitForElement($formStatusElement);
    $i->seeOptionIsSelected($formStatusElement, 'Subscribed');

    $i->wantTo('Verify that "unsubscribe" link works and subscriber can confirm switching status to unsubscribed');
    $i->amOnMailboxAppPage();
    $i->click(Locator::contains('span.subject', $this->newsletterTitle));
    $i->switchToIframe('#preview-html');
    $i->waitForElementChange(
      \Codeception\Util\Locator::contains('a', 'Unsubscribe'),
      function ($el) {
            return $el->getAttribute('target') === "_blank";
      },
      100
    );
    $i->click('Unsubscribe');
    $i->switchToNextTab();
    $confirmUnsubscribe = 'Yes, unsubscribe me';
    $i->waitForText($confirmUnsubscribe);
    $i->click($confirmUnsubscribe, '.mailpoet_confirm_unsubscribe');
    $i->waitForText('You are now unsubscribed');
    $i->click('Manage your subscription');
    $i->seeOptionIsSelected($formStatusElement, 'Unsubscribed');
    $i->seeNoJSErrors();
  }

  private function sendEmail(\AcceptanceTester $i) {
    $segmentName = $i->createListWithSubscriber();

    $i->login();
    $i->amOnMailpoetPage('Emails');

    // step 1 - select type
    $i->click('[data-automation-id="create_standard"]');

    // step 2 - select template
    $firstTemplateElement = $i->checkTemplateIsPresent(0);
    $i->click($firstTemplateElement);

    // step 3 - design newsletter (update subject)
    $titleElement = '[data-automation-id="newsletter_title"]';
    $i->waitForElement($titleElement);
    $i->fillField($titleElement, $this->newsletterTitle);
    $i->click('Next');

    // step 4 - send
    $searchFieldElement = 'textarea.select2-search__field';
    $i->waitForElement($searchFieldElement);
    $i->selectOptionInSelect2($segmentName);
    $i->click('Send');

    $i->waitForElement('[data-automation-id="newsletters_listing_tabs"]');

    // Reloading page is faster than waiting for regular AJAX request to refresh it
    for ($index = 0; $index < 15; $index++) {
      try {
        $i->wait(2);
        $i->reloadPage();
        $i->waitForListingItemsToLoad();
        $i->see('1 / 1');
        return;
      } catch (Exception $e) {
        continue;
      }
    }
    $i->waitForText('Sent to 1 of 1');
    $i->see('Sent to 1 of 1');
  }
}

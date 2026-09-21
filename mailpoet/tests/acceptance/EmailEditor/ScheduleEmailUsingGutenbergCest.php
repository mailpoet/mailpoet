<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use Facebook\WebDriver\WebDriverKeys;
use MailPoet\Test\DataFactories\Settings;

/**
 * @group gutenberg-latest
 */
class ScheduleEmailUsingGutenbergCest {
  public function scheduleEmailStoresSiteLocalTimeAsUtc(\AcceptanceTester $i, $scenario) {
    if (!$i->checkEmailEditorRequiredWordpressVersion()) {
      $scenario->skip('Temporally skip this test because new email editor is not compatible with WP versions below ' . \AcceptanceTester::EMAIL_EDITOR_MINIMAL_WP_VERSION);
    }

    // The Selenium browser always runs in UTC. Putting the site on a different,
    // fixed-offset zone exercises the exact mismatch the bug was about: a
    // site-local wall-clock time getting stored and read back as if it were
    // UTC. A fixed offset (rather than a DST-observing named zone) keeps the
    // math in this test simple and independent of the date it runs on.
    $i->cli(['option', 'update', 'timezone_string', 'Etc/GMT-2']);

    $settings = new Settings();
    $settings->withCronTriggerMethod('Action Scheduler');
    $settings->withSender('John Doe', 'john@doe.com');
    $segmentName = $i->createListWithSubscriber();

    $i->wantTo('Create a standard email using the Gutenberg editor');
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('[data-automation-id="create_standard_email_dropdown"]');
    $i->waitForText('Choose an email editor');
    $i->click('[data-automation-id="editor_choice_block"]');
    $i->click('[data-automation-id="editor_choice_continue"]');
    $this->closeTemplateSelectionModal($i);

    $i->waitForElement('[name="editor-canvas"]');
    $i->wait(1); // wait for the iframe to initialize, otherwise the switch does not work properly
    $i->switchToIFrame('[name="editor-canvas"]');
    $i->waitForElementVisible('.is-root-container', 20);
    $i->waitForElementVisible('[aria-label="Block: Image"]');
    $i->switchToIFrame();

    $i->click('Save draft', '.edit-post-header');
    $i->waitForText('Saved');

    $i->wantTo('Open the send panel and select recipients');
    $i->click('[data-automation-id="email_editor_send_button"]');
    $i->waitForElementVisible('.mailpoet-send-panel');
    $i->click('.mailpoet-send-panel__recipients .components-panel__body-toggle');
    $i->waitForElementVisible('.mailpoet-status-panel__recipients-segments');
    $i->fillField('.mailpoet-status-panel__recipients-segments input', $segmentName);
    $i->pressKey('.mailpoet-status-panel__recipients-segments input', WebDriverKeys::ENTER);

    $i->wantTo('Schedule the email for 2:00 pm site-local time, a couple of days out');
    $i->click('//button[contains(., "Send:")]', '.mailpoet-send-panel');
    $i->waitForElementVisible('.components-datetime');

    // A few days out so this never lands on a past date, regardless of when the
    // suite runs. Year and month are set before day so the day field's max-days
    // validation already matches the target month.
    $futureDate = new \DateTimeImmutable('+2 days');
    $this->setNumberField($i, 'Year', $futureDate->format('Y'));
    $i->selectOption('Month', $futureDate->format('F'));
    $this->setNumberField($i, 'Day', $futureDate->format('j'));
    $this->setNumberField($i, 'Hours', '2');
    $this->setNumberField($i, 'Minutes', '00');
    $i->click('PM');

    $i->wantTo('Confirm the send panel itself shows the picked site-local time');
    $i->waitForText('2:00 pm', 10, ['xpath' => '//button[contains(., "Send:")]']);

    $i->wantTo('Schedule the email');
    $i->waitForElementClickable('[data-automation-id="email_send_panel_send_button"]');
    $i->click('[data-automation-id="email_send_panel_send_button"]');

    $i->wantTo('Confirm the listing shows the same site-local wall-clock time that was picked');
    $i->waitForElement('[data-automation-id="newsletters_listing_tabs"]');
    $i->waitForText('2:00 pm');

    $i->wantTo('Change the site timezone and confirm the displayed time shifts accordingly');
    // Proves the stored value is genuinely UTC, not just cosmetically correct:
    // Etc/GMT+10 is UTC-10, twelve hours behind Etc/GMT-2's UTC+2, so 2:00 pm
    // in the original zone reads back as 2:00 am in the new one.
    $i->cli(['option', 'update', 'timezone_string', 'Etc/GMT+10']);
    $i->reloadPage();
    $i->waitForElement('[data-automation-id="newsletters_listing_tabs"]');
    $i->waitForText('2:00 am');
  }

  /**
   * The date/time picker's number fields (@wordpress/components' TimePicker)
   * are controlled inputs that don't reliably commit simulated keystrokes:
   * `fillField`'s clear-then-type leaves the field's label locator unable to
   * find the element again on the very next step. Setting the value via the
   * DOM directly and dispatching the same input/keydown events React listens
   * for is the reliable way to drive them.
   */
  private function setNumberField(\AcceptanceTester $i, string $label, string $value): void {
    $i->executeJS(
      <<<'JS'
      var labelText = arguments[0];
      var value = arguments[1];
      var label = Array.from(document.querySelectorAll('label'))
        .find(function (el) { return el.textContent.trim() === labelText; });
      var input = document.getElementById(label.getAttribute('for'));
      var setter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
      setter.call(input, value);
      input.dispatchEvent(new Event('input', { bubbles: true }));
      input.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true }));
      JS,
      [$label, $value]
    );
  }

  private function closeTemplateSelectionModal(\AcceptanceTester $i): void {
    $i->wantTo('Close template selector');
    $i->waitForElementClickable('.email-editor-start_from_scratch_button');
    $i->waitForText('Newsletter - Newsletter');
    $i->click('[aria-label="Newsletter"]');
    $i->waitForElementVisible('.block-editor-block-preview__container');
    $i->click('[aria-label="Close"]');
  }
}

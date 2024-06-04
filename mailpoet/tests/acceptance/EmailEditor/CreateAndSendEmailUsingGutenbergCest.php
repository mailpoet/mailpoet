<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Features\FeaturesController;
use MailPoet\Test\DataFactories\Features;
use MailPoet\Test\DataFactories\Settings;

class CreateAndSendEmailUsingGutenbergCest {
  public function createAndSendStandardNewsletter(\AcceptanceTester $i, $scenario) {
    if (!$this->checkMinimalWordpressVersion($i)) {
      $scenario->skip('Temporally skip this test because new email editor is not compatible with WP versions below 6.4');
    }
    $settings = new Settings();
    $settings->withCronTriggerMethod('Action Scheduler');
    $settings->withSender('John Doe', 'john@doe.com');
    (new Features())->withFeatureEnabled(FeaturesController::GUTENBERG_EMAIL_EDITOR);
    $segmentName = $i->createListWithSubscriber();

    $i->wantTo('Create standard newsletter using Gutenberg editor');
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('[data-automation-id="create_standard_email_dropdown"]');
    $i->waitForText('Create using new editor (Beta)');
    $i->click('Create using new editor (Beta)');
    $i->waitForText('Create modern, beautiful emails that embody your brand with advanced customization and editing capabilities.');
    $i->click('//button[text()="Continue"]');

    $this->closeTemplateSelectionModal($i);

    $i->wantTo('Compose an email');
    $i->waitForElement('.editor-canvas__iframe');
    $i->switchToIFrame('.editor-canvas__iframe');
    $i->waitForElementVisible('.is-root-container', 20);
    $i->waitForElementVisible('[aria-label="Block: Image"]');
    $i->waitForElementVisible('[aria-label="Block: Heading"]');
    $i->click('[aria-label="Block: Paragraph"]');
    $i->type('Sample text');
    $i->switchToIFrame();

    $i->wantTo('Verify correct WP menu item is highlighted');
    $i->waitForText('Emails', 10, '#toplevel_page_mailpoet-homepage .current');

    $i->wantTo('Change Campaign name');
    $i->click('New Email');
    $i->waitForElement('[name="campaign_name"]');
    $i->clearFormField('[name="campaign_name"]');
    $i->type('My Campaign Name');

    $i->wantTo('Change subject and preheader');
    $i->click('[aria-label="Change campaign name"]');
    $i->click('Email', '.editor-sidebar__panel-tabs');
    $i->fillField('[data-automation-id="email_subject"]', 'My New Subject');
    $i->fillField('[data-automation-id="email_preview_text"]', 'My New Preview Text');

    $i->wantTo('Send an email and verify it was delivered');
    $i->click('Save Draft');
    $i->waitForText('Saved');
    $i->waitForText('Email saved!');
    $i->click('Send');
    $i->waitForElement('[name="subject"]');
    $subject = $i->grabValueFrom('[name="subject"]');
    verify($subject)->equals('My New Subject');
    $i->waitForText('My New Preview Text');
    $i->fillField('sender_name', 'John Doe');
    $i->fillField('sender_address', 'john.doe@example.com');
    $i->selectOptionInSelect2($segmentName);

    $i->click('Send');
    $i->waitForEmailSendingOrSent();

    $i->triggerMailPoetActionScheduler();

    $i->wantTo('Confirm the newsletter campaign name was saved');
    $i->amOnMailpoetPage('Emails');
    $i->waitForText('My Campaign Name', 10, '[data-automation-id="newsletters_listing_tabs"]');

    $i->wantTo('Confirm the newsletter was received');
    $i->checkEmailWasReceived('My New Subject');
  }

  public function displayNewsletterPreview(\AcceptanceTester $i, $scenario) {
    if (!$this->checkMinimalWordpressVersion($i))
      $scenario->skip('Temporally skip this test because new email editor is not compatible with WP versions below 6.4');

    $settings = new Settings();
    $settings->withCronTriggerMethod('Action Scheduler');
    $settings->withSender('John Doe', 'john@doe.com');
    (new Features())->withFeatureEnabled(FeaturesController::GUTENBERG_EMAIL_EDITOR);

    $i->wantTo('Open standard newsletter using Gutenberg editor');
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('[data-automation-id="create_standard_email_dropdown"]');
    $i->waitForText('Create using new editor (Beta)');
    $i->click('Create using new editor (Beta)');
    $i->waitForText('Create modern, beautiful emails that embody your brand with advanced customization and editing capabilities.');
    $i->click('//button[text()="Continue"]');

    $this->closeTemplateSelectionModal($i);

    $i->wantTo('Edit an email');
    $i->waitForElement('.editor-canvas__iframe');
    $i->switchToIFrame('.editor-canvas__iframe');
    $i->waitForElementVisible('.is-root-container', 20);
    $i->waitForElementVisible('[aria-label="Block: Image"]');
    $i->waitForElementVisible('[aria-label="Block: Heading"]');
    $i->click('[aria-label="Block: Paragraph"]');
    $i->type('Sample text');
    $i->switchToIFrame();

    $i->wantTo('Save draft and display preview');
    $i->click('Save Draft');
    $i->waitForText('Saved');
    $i->waitForText('Email saved!');
    // there is weird issue in the acceptance env where preview popup goes beyond sidebar
    // this issue is not confirmed to be real issue but only on the acceptance test site
    $i->click('div.interface-pinned-items > button'); // close sidebar
    $i->click('.mailpoet-preview-dropdown button[aria-label="Preview"]');
    $i->waitForElementVisible('//button[text()="Preview in new tab"]');
    $i->waitForElementClickable('//button[text()="Preview in new tab"]');
    $i->click('//button[text()="Preview in new tab"]');
    $i->switchToNextTab();
    $i->canSeeInCurrentUrl('endpoint=view_in_browser');
    $i->canSee('Sample text');
    $i->closeTab();

    $i->wantTo('Send preview email and verify it was delivered');
    $i->click('//span[text()="Send a test email"]'); // MenuItem component renders a button containing span
    $i->waitForElementClickable('//button[text()="Send test email"]');
    $i->click('//button[text()="Send test email"]');
    $i->waitForText('Test email sent successfully!');
    $i->click('//button[text()="Close"]');
    $i->waitForElementNotVisible('//button[text()="Send test email"]');
  }

  private function checkMinimalWordpressVersion(\AcceptanceTester $i): bool {
    $wordPressVersion = $i->getWordPressVersion();
    // New email editor is not compatible with WP versions below 6.5
    if (version_compare($wordPressVersion, '6.5', '<')) {
      return false;
    }
    return true;
  }

  private function closeTemplateSelectionModal(\AcceptanceTester $i): void {
    $i->wantTo('Close template selector');
    $i->waitForElementVisible('.block-editor-block-preview__container');
    $i->click('[aria-label="Close"]');
  }
}

<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Settings;

class CreateAndSendEmailUsingGutenbergCest {
  public function createAndSendStandardNewsletter(\AcceptanceTester $i, $scenario) {
    if (!$i->checkEmailEditorRequiredWordpressVersion()) {
      $scenario->skip('Temporally skip this test because new email editor is not compatible with WP versions below ' . \AcceptanceTester::EMAIL_EDITOR_MINIMAL_WP_VERSION);
    }
    $settings = new Settings();
    $settings->withCronTriggerMethod('Action Scheduler');
    $settings->withSender('John Doe', 'john@doe.com');
    $segmentName = $i->createListWithSubscriber();

    $i->wantTo('Create standard newsletter using Gutenberg editor');
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('[data-automation-id="create_standard_email_dropdown"]');
    $i->waitForText('Create using the new email editor (Alpha)');
    $i->click('Create using the new email editor (Alpha)');
    $i->waitForText('Create modern, beautiful emails that embody your brand with advanced customization and editing capabilities.');
    $i->click('//button[text()="Continue"]');

    $this->closeTemplateSelectionModal($i);

    $i->wantTo('Compose an email');
    $i->waitForElement('[name="editor-canvas"]');
    $i->wait(1); // we need to wait for the iframe to initialize otherwise the switch does not work properly
    $i->switchToIFrame('[name="editor-canvas"]');
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
    $i->fillField('[data-automation-id="email_preheader"]', 'My New Preview Text');

    $i->wantTo('Send an email and verify it was delivered');
    $i->click('Save Draft');
    $i->waitForText('Saved');
    $i->waitForText('Email saved!');
    $i->click('[aria-label="Close sidebar"]'); // close the sidebar. It sometimes interferes with the send button click
    $i->click('[data-automation-id="email_editor_send_button"]');
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
    if (!$i->checkEmailEditorRequiredWordpressVersion()) {
      $scenario->skip('Temporally skip this test because new email editor is not compatible with WP versions below ' . \AcceptanceTester::EMAIL_EDITOR_MINIMAL_WP_VERSION);
    }
    $settings = new Settings();
    $settings->withCronTriggerMethod('Action Scheduler');
    $settings->withSender('John Doe', 'john@doe.com');

    $i->wantTo('Open standard newsletter using Gutenberg editor');
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('[data-automation-id="create_standard_email_dropdown"]');
    $i->waitForText('Create using the new email editor (Alpha)');
    $i->click('Create using the new email editor (Alpha)');
    $i->waitForText('Create modern, beautiful emails that embody your brand with advanced customization and editing capabilities.');
    $i->click('//button[text()="Continue"]');

    $this->closeTemplateSelectionModal($i);

    $i->wantTo('Edit an email');
    $i->waitForElement('[name="editor-canvas"]');
    $i->wait(1); // we need to wait for the iframe to initialize otherwise the switch does not work properly
    $i->switchToIFrame('[name="editor-canvas"]');
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
    $i->click('[aria-label="Close sidebar"]'); // close sidebar
    $i->click('.mailpoet-preview-dropdown button[aria-label="Preview"]');
    $i->waitForElementVisible('//a[text()="Preview in new tab"]');
    $i->waitForElementClickable('//a[text()="Preview in new tab"]');
    $i->click('//a[text()="Preview in new tab"]');
    $i->switchToNextTab();
    $i->canSeeInCurrentUrl('post_type=mailpoet_email');
    $i->canSee('Sample text');
    $i->closeTab();

    $i->wantTo('Send preview email and verify it was delivered');
    $i->click('.mailpoet-preview-dropdown button[aria-label="Preview"]');
    $i->waitForElementVisible('//span[text()="Send a test email"]');
    $i->click('//span[text()="Send a test email"]'); // MenuItem component renders a button containing span
    $i->waitForElementClickable('//button[text()="Send test email"]');
    $i->click('//button[text()="Send test email"]');
    $i->waitForText('Test email sent successfully!');
    $i->click('//button[text()="Close"]');
    $i->waitForElementNotVisible('//button[text()="Send test email"]');
  }

  private function closeTemplateSelectionModal(\AcceptanceTester $i): void {
    $i->wantTo('Close template selector');
    $i->waitForElementClickable('.email-editor-start_from_scratch_button');
    $i->click('[aria-label="Basic"]');
    $i->waitForElementVisible('.block-editor-block-preview__container');
    $i->click('[aria-label="Close"]');
  }
}

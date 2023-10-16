<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Features\FeaturesController;
use MailPoet\Test\DataFactories\Features;
use MailPoet\Test\DataFactories\Settings;

class CreateAndSendEmailUsingGutenbergCest {
  public function createAndSendStandardNewsletter(\AcceptanceTester $i) {
    $wordPressVersion = $i->getWordPressVersion();
    if (version_compare($wordPressVersion, '6.2', '<')) {
      return;
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

    $i->wantTo('Compose an email');
    if (version_compare($wordPressVersion, '6.3', '<')) {
      $i->waitForElement('.wp-block-post-content');
      $i->click('.wp-block-post-content');
      $i->type('Hello world!');
    } else {
      // Version 6.3 introduced a new Gutenberg editor with an iframe
      $i->switchToFrame('[name="editor-canvas"]');
      $i->waitForElement('.wp-block-post-content');
      $i->click('.wp-block-post-content');
      $i->type('Hello world!');
      $i->switchToIFrame();
    }

    $i->wantTo('Close fullscreen mode and verify correct WP menu item is highlighted');
    $i->click('[aria-label="Options"]');
    $i->waitForText('Fullscreen mode');
    $i->click('Fullscreen mode');
    $i->waitForText('Emails', 10, '#toplevel_page_mailpoet-homepage .current');
    $i->wantTo('Close options dropdown');
    $i->click('[aria-label="Options"]');

    $i->wantTo('Change subject and preheader');
    $i->click('button[data-label="Email"]');
    $i->click('//button[text()="Details"]');
    $i->fillField('[data-automation-id="email_subject"]', 'My New Subject');
    $i->fillField('[data-automation-id="email_preview_text"]', 'My New Preview Text');

    $i->wantTo('Send an email and verify it was delivered');
    $i->click('Next');
    $i->waitForText('My New Subject');
    $i->waitForText('My New Preview Text');
    $i->waitForElement('[name="subject"]');
    $i->fillField('subject', 'Test Subject');
    $i->fillField('sender_name', 'John Doe');
    $i->fillField('sender_address', 'john.doe@example.com');
    $i->selectOptionInSelect2($segmentName);

    $i->click('Send');
    $i->waitForEmailSendingOrSent();

    $i->triggerMailPoetActionScheduler();

    $i->wantTo('Confirm the newsletter was received');
    $i->checkEmailWasReceived('Test Subject');
  }

  public function displayNewsletterPreview(\AcceptanceTester $i) {
    $wordPressVersion = $i->getWordPressVersion();
    if (version_compare($wordPressVersion, '6.2', '<')) {
      return;
    }
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

    $i->wantTo('Edit an email');
    if (version_compare($wordPressVersion, '6.3', '<')) {
      $i->waitForElement('.wp-block-post-content');
      $i->click('.wp-block-post-content');
      $i->type('Hello world!');
    } else {
      // Version 6.3 introduced a new Gutenberg editor with an iframe
      $i->switchToFrame('[name="editor-canvas"]');
      $i->waitForElement('.wp-block-post-content');
      $i->click('.wp-block-post-content');
      $i->type('Hello world!');
      $i->switchToIFrame();
    }

    $i->wantTo('Save draft and display preview');
    $i->click('//button[text()="Save draft"]');
    $i->click('.mailpoet-preview-dropdown button[aria-label="Preview"]');
    $i->waitForElementClickable('//button[text()="Preview in new tab"]');
    $i->click('//button[text()="Preview in new tab"]');
    $i->switchToNextTab();
    $i->canSeeInCurrentUrl('endpoint=view_in_browser');
    $i->canSee('Hello world!');
    $i->closeTab();

    $i->wantTo('Send preview email and verify it was delivered');
    $i->click('//span[text()="Send a test email"]'); // MenuItem component renders a button containing span
    $i->waitForElementClickable('//button[text()="Send test email"]');
    $i->click('//button[text()="Send test email"]');
    $i->waitForText('Test email sent successfully!');
    $i->click('//button[text()="Close"]');
    $i->waitForElementNotVisible('//button[text()="Send test email"]');
  }
}

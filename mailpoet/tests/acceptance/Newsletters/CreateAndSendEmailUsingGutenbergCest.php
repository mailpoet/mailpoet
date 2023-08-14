<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Features\FeaturesController;
use MailPoet\Test\DataFactories\Features;
use MailPoet\Test\DataFactories\Settings;

class CreateAndSendEmailUsingGutenbergCest {
  public function createAndSendStandardNewsletter(\AcceptanceTester $i) {
    $settings = new Settings();
    $settings->withCronTriggerMethod('Action Scheduler');
    $settings->withSender('John Doe', 'john@doe.com');
    (new Features())->withFeatureEnabled(FeaturesController::GUTENBERG_EMAIL_EDITOR);
    $segmentName = $i->createListWithSubscriber();

    $i->wantTo('Create standard newsletter using Gutenberg editor');
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('[data-automation-id="create_standard"]');
    $i->waitForText('Which editor do you want to use?');
    $i->click('Gutenberg Editor');

    $i->wantTo('Compose an email');
    $i->waitForElement('.wp-block-post-content');
    $i->click('.wp-block-post-content');
    $i->type('Hello world!');

    $i->wantTo('Close fullscreen mode and verify correct WP menu item is highlighted');
    $i->click('[aria-label="Options"]');
    $i->waitForText('Fullscreen mode');
    $i->click('Fullscreen mode');
    $i->waitForText('Emails', 10, '#toplevel_page_mailpoet-homepage .current');

    $i->wantTo('Send an email and verify it was delivered');
    $i->click('Next');
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
}

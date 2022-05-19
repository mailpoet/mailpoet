<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Settings;

class PreviewStandardNewsletterCest {

  /** @var Settings */
  private $settings;

  public function _before() {
    $this->settings = new Settings();
  }

  public function previewStandardNewsletter(\AcceptanceTester $i) {
    $newsletterName = 'Preview in Browser Newsletter';
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletterName)->create();
    $i->wantTo('Preview a standard newsletter');
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->waitForText($newsletterName);
    $i->clickItemRowActionByItemName($newsletterName, 'Preview');
    $i->switchToNextTab();
    $i->waitForElement('.mailpoet_template');
  }

  public function previewStandardNewsletterInEditor(\AcceptanceTester $i) {
    $i->wantTo('Preview and send newsletter inside editor');
    $this->settings->withCronTriggerMethod('Action Scheduler');
    $newsletterName = 'Preview in Browser Newsletter';
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletterName)->create();
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->waitForText($newsletterName);
    $i->clickItemRowActionByItemName($newsletterName, 'Edit');
    $i->waitForElement('.mailpoet_show_preview');
    $i->click('.mailpoet_show_preview');
    $i->waitForElement('[data-automation-id="switch_send_to_email"]');
    $i->click('[data-automation-id="switch_send_to_email"]');
    //test sending preview to email
    $i->waitForText('Send preview');
    $i->click('Send preview');
    $i->waitForText('Your test email has been sent!');
    $i->click('#mailpoet_modal_close');
    //check for error if no email is set
    $i->waitForElement('.mailpoet_show_preview');
    $i->click('.mailpoet_show_preview');
    $i->waitForElement('[data-automation-id="switch_send_to_email"]');
    $i->click('[data-automation-id="switch_send_to_email"]');
    $i->clearField('#mailpoet_preview_to_email');
    $i->waitForText('Send preview');
    $i->click('Send preview');
    $i->waitForText('Enter an email address to send the preview newsletter to.');
    //set different email and test sending
    $i->fillField('#mailpoet_preview_to_email', 'test2@test.com');
    $i->click('Send preview');
    $i->waitForText('Your test email has been sent!');
    //confirm if preview newsletter is received at the end
    $i->checkEmailWasReceived($newsletterName);
    $i->click(Locator::contains('span.subject', $newsletterName));
  }
}

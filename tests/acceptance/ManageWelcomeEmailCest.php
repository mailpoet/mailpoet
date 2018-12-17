<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

require_once __DIR__ . '/../DataFactories/Newsletter.php';

class ManageWelcomeEmailCest {

  private $welcome_template;
  private $title_element;

  function __construct() {
    $this->welcome_template = '[data-automation-id="select_template_0"]';
    $this->title_element = '[data-automation-id="newsletter_title"]';
  }

  private function createWelcomeEmailWithTitle(\AcceptanceTester $I, $newsletter_title) {
    $newsletter_factory = new Newsletter();
    $newsletter_factory->withSubject($newsletter_title)
     ->withWelcomeType()
     ->create();
  }

  function saveWelcomeNewsletterAsDraft(\AcceptanceTester $I) {
    $I->wantTo('save a welcome newsletter as a draft');
    $newsletter_title = 'Save Welcome Email As Draft Test Email';
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id="new_email"]');
    $I->seeInCurrentUrl('#/new');
    $I->click('[data-automation-id="create_welcome"]');
    $I->waitForText('Welcome Email', 20);
    $I->seeInCurrentUrl('mailpoet-newsletters#/new/welcome');
    $I->click('Next');
    $I->waitForElement($this->welcome_template, 20);
    $I->see('Welcome Emails', ['css' => 'a.current']);
    $I->seeInCurrentUrl('#/template');
    $I->click($this->welcome_template);
    $I->waitForElement($this->title_element, 20);
    $I->seeInCurrentUrl('mailpoet-newsletter-editor');
    $I->fillField($this->title_element, $newsletter_title);
    $I->click('Next');
    $I->waitForText('Reply-to', 20);
    $I->click('Save as draft and close');
    $I->waitForElement('[data-automation-id="newsletters_listing_tabs"]', 20);
    $I->seeInCurrentUrl('mailpoet-newsletters');
    $I->click('Welcome Emails', '[data-automation-id="newsletters_listing_tabs"]');
    $I->waitForText($newsletter_title, 20);
  }

  function editWelcomeEmail(\AcceptanceTester $I) {
    $newsletter_title = 'Edit Welcome Email Test';
    $this->createWelcomeEmailWithTitle($I, $newsletter_title);
    $I->wantTo('Edit a welcome newsletter');
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('Welcome Emails', '[data-automation-id="newsletters_listing_tabs"]');
    $I->waitForText($newsletter_title, 20);
    $I->clickItemRowActionByItemName($newsletter_title, 'Edit');
    $I->waitForElement($this->title_element, 10);
    $I->seeInCurrentUrl('mailpoet-newsletter-editor');
    $I->fillField($this->title_element, 'Edit Test Welcome Edited');
    $I->click('Next');
    $I->waitForText('Reply-to', 20);
    $I->click('Save as draft and close');
    $I->amOnMailpoetPage('Emails');
    $I->waitForElement('[data-automation-id="newsletters_listing_tabs"]', 20);
    $I->seeInCurrentUrl('mailpoet-newsletters');
    $I->click('Welcome Emails', '[data-automation-id="newsletters_listing_tabs"]');
    $I->waitForText('Edit Test Welcome Edited', 20);
  }

  function deleteWelcomeEmail(\AcceptanceTester $I) {
    $I->wantTo('Delete a welcome email');
    $newsletter_title = 'Delete Welcome Email Test';
    $this->createWelcomeEmailWithTitle($I, $newsletter_title);
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('Welcome Emails', '[data-automation-id="newsletters_listing_tabs"]');
    $I->waitForText($newsletter_title, 20);
    $I->clickItemRowActionByItemName($newsletter_title, 'Move to trash');
    $I->waitForElement('[data-automation-id="filters_trash"]');
    $I->click('[data-automation-id="filters_trash"]');
    $I->waitForText($newsletter_title);
    $I->clickItemRowActionByItemName($newsletter_title, 'Restore');
    $I->amOnMailpoetPage('Emails');
    $I->click('Welcome Emails', '[data-automation-id="newsletters_listing_tabs"]');
    $I->waitForText($newsletter_title, 15);
  }

  function duplicateWelcomeEmail (\AcceptanceTester $I) {
    $newsletter_title = 'Duplicate Welcome Email Test';
    $this->createWelcomeEmailWithTitle($I, $newsletter_title);
    $I->wantTo('Duplicate a welcome email');
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('Welcome Emails', '[data-automation-id="newsletters_listing_tabs"]');
    $I->waitForText($newsletter_title, 20);
    $I->clickItemRowActionByItemName($newsletter_title, 'Duplicate');
    $I->waitForText('Copy of ' . $newsletter_title, 10);
  }

  function searchForWelcomeEmail (\AcceptanceTester $I) {
    $I->wantTo('Search for a welcome email');
    $newsletter_title = 'Welcome Email Search Test';
    $failure_condition_newsletter = 'Totes Fake';
    $this->createWelcomeEmailWithTitle($I, $newsletter_title);
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('Welcome Emails', '[data-automation-id="newsletters_listing_tabs"]');
    $I->waitForText($newsletter_title, 20);
    $I->searchFor($failure_condition_newsletter, 2);
    $I->wait(5);
    $I->waitForElement('tr.no-items', 10);
    $I->searchFor($newsletter_title);
    $I->waitForText($newsletter_title, 10);
  }

  function saveWelcomeEmailAsTemplate (\AcceptanceTester $I) {
    $I->wantTo('Save welcome email as a template');
    $newsletter_title = 'Save Welcome Email As Template Test';
    $template_title = 'Welcome Template Test Title';
    $template_descr = 'Welcome Template Test Descr';
    $this->createWelcomeEmailWithTitle($I, $newsletter_title);

    $save_template_option = '[data-automation-id="newsletter_save_as_template_option"]';
    $save_template_button = '[data-automation-id="newsletter_save_as_template_button"]';

    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('Welcome Emails', '[data-automation-id="newsletters_listing_tabs"]');
    $I->waitForText($newsletter_title, 20);
    $I->clickItemRowActionByItemName($newsletter_title, 'Edit');
    $I->waitForElement($this->title_element, 10);
    $I->seeInCurrentUrl('mailpoet-newsletter-editor');
    $I->click('[data-automation-id="newsletter_save_options_toggle"]');
    $I->waitForElement($save_template_option, 10);
    $I->click($save_template_option);
    $I->waitForElement($save_template_button, 10);
    $I->fillField('template_name', $template_title);
    $I->fillField('template_description', $template_descr);
    $I->click($save_template_button);
    $I->waitForText('Template has been saved.', 20);
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id="new_email"]');
    $I->seeInCurrentUrl('#/new');
    $I->click('[data-automation-id="create_welcome"]');
    $I->seeInCurrentUrl('#/new/welcome');
    $I->click('Next');
    $I->waitForElement($this->welcome_template, 20);
    $I->see('Welcome Emails', ['css' => 'a.current']);
    $I->seeInCurrentUrl('#/template');
    $I->see($template_title);
    $I->click(['xpath' => '//*[text()="' . $template_title . '"]//ancestor::*[@data-automation-id="select_template_box"]//*[starts-with(@data-automation-id,"select_template_")]']);
    $I->waitForElement('[data-automation-id="newsletter_title"]');
    $I->seeInCurrentUrl('mailpoet-newsletter-editor');
    $I->seeNoJSErrors();
  }

}

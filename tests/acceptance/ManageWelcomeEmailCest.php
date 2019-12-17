<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class ManageWelcomeEmailCest {

  private $welcomeTemplate;
  private $titleElement;

  function __construct() {
    $this->welcomeTemplate = '[data-automation-id="select_template_0"]';
    $this->titleElement = '[data-automation-id="newsletter_title"]';
  }

  private function createWelcomeEmailWithTitle(\AcceptanceTester $I, $newsletterTitle) {
    return (new Newsletter())
        ->withSubject($newsletterTitle)
        ->withWelcomeTypeForSegment()
        ->create();
  }

  function saveWelcomeNewsletterAsDraft(\AcceptanceTester $I) {
    $I->wantTo('save a welcome newsletter as a draft');
    $newsletterTitle = 'Save Welcome Email As Draft Test Email';
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->seeInCurrentUrl('#/new');
    $I->click('[data-automation-id="create_welcome"]');
    $I->waitForText('Welcome Email');
    $I->click('Next');
    $I->waitForElement($this->welcomeTemplate);
    $I->see('Welcome Emails', ['css' => 'a.current']);
    $I->click($this->welcomeTemplate);
    $I->waitForElement($this->titleElement);
    $I->fillField($this->titleElement, $newsletterTitle);
    $I->click('Next');
    $I->waitForText('Reply-to');
    $I->click('Save as draft and close');
    $I->waitForElement('[data-automation-id="newsletters_listing_tabs"]');
    $I->click('Welcome Emails', '[data-automation-id="newsletters_listing_tabs"]');
    $I->waitForText($newsletterTitle);
  }

  function editWelcomeEmail(\AcceptanceTester $I) {
    $newsletter = $this->createWelcomeEmailWithTitle($I, 'Edit Welcome Email Test');
    $I->wantTo('Edit a welcome newsletter');
    $I->login();
    $I->amEditingNewsletter($newsletter->id);
    $I->fillField($this->titleElement, 'Edit Test Welcome Edited');
    $I->click('Next');
    $I->waitForText('Reply-to');
    $I->click('Save as draft and close');
    $I->amOnMailpoetPage('Emails');
    $I->waitForElement('[data-automation-id="newsletters_listing_tabs"]');
    $I->click('Welcome Emails', '[data-automation-id="newsletters_listing_tabs"]');
    $I->waitForText('Edit Test Welcome Edited');
  }

  function deleteWelcomeEmail(\AcceptanceTester $I) {
    $I->wantTo('Delete a welcome email');
    $newsletterTitle = 'Delete Welcome Email Test';
    $this->createWelcomeEmailWithTitle($I, $newsletterTitle);
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('Welcome Emails', '[data-automation-id="newsletters_listing_tabs"]');
    $I->waitForText($newsletterTitle);
    $I->clickItemRowActionByItemName($newsletterTitle, 'Move to trash');
    $I->waitForElement('[data-automation-id="filters_trash"]');
    $I->click('[data-automation-id="filters_trash"]');
    $I->waitForText($newsletterTitle);
    $I->clickItemRowActionByItemName($newsletterTitle, 'Restore');
    $I->amOnMailpoetPage('Emails');
    $I->click('Welcome Emails', '[data-automation-id="newsletters_listing_tabs"]');
    $I->waitForText($newsletterTitle);
  }

  function duplicateWelcomeEmail (\AcceptanceTester $I) {
    $newsletterTitle = 'Duplicate Welcome Email Test';
    $this->createWelcomeEmailWithTitle($I, $newsletterTitle);
    $I->wantTo('Duplicate a welcome email');
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('Welcome Emails', '[data-automation-id="newsletters_listing_tabs"]');
    $I->waitForText($newsletterTitle);
    $I->clickItemRowActionByItemName($newsletterTitle, 'Duplicate');
    $I->waitForText('Copy of ' . $newsletterTitle);
  }

  function searchForWelcomeEmail (\AcceptanceTester $I) {
    $I->wantTo('Search for a welcome email');
    $newsletterTitle = 'Welcome Email Search Test';
    $failureConditionNewsletter = 'Totes Fake';
    $this->createWelcomeEmailWithTitle($I, $newsletterTitle);
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('Welcome Emails', '[data-automation-id="newsletters_listing_tabs"]');
    $I->waitForText($newsletterTitle);
    $I->searchFor($failureConditionNewsletter);
    $I->waitForElement('tr.no-items');
    $I->searchFor($newsletterTitle);
    $I->waitForText($newsletterTitle);
  }

  function saveWelcomeEmailAsTemplate (\AcceptanceTester $I) {
    $I->wantTo('Save welcome email as a template');
    $templateTitle = 'Welcome Template Test Title';
    $templateDescr = 'Welcome Template Test Descr';
    $newsletter = $this->createWelcomeEmailWithTitle($I, 'Save Welcome Email As Template Test');

    $saveTemplateOption = '[data-automation-id="newsletter_save_as_template_option"]';
    $saveTemplateButton = '[data-automation-id="newsletter_save_as_template_button"]';

    $I->login();
    $I->amEditingNewsletter($newsletter->id);
    $I->click('[data-automation-id="newsletter_save_options_toggle"]');
    $I->waitForElement($saveTemplateOption);
    $I->click($saveTemplateOption);
    $I->waitForElement($saveTemplateButton);
    $I->fillField('template_name', $templateTitle);
    $I->fillField('template_description', $templateDescr);
    $I->click($saveTemplateButton);
    $I->waitForText('Template has been saved.');
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id="new_email"]');
    $I->click('[data-automation-id="create_welcome"]');
    $I->click('Next');
    $I->waitForElement($this->welcomeTemplate);
    $I->see('Welcome Emails', ['css' => 'a.current']);
    $I->see($templateTitle);
    $I->click(['xpath' => '//*[text()="' . $templateTitle . '"]//ancestor::*[@data-automation-id="select_template_box"]//*[starts-with(@data-automation-id,"select_template_")]']);
    $I->waitForElement('[data-automation-id="newsletter_title"]');
    $I->seeNoJSErrors();
  }

}

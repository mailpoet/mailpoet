<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class ManageWelcomeEmailCest {

  private $titleElement;

  public function __construct() {
    $this->titleElement = '[data-automation-id="newsletter_title"]';
  }

  private function createWelcomeEmailWithTitle(\AcceptanceTester $i, $newsletterTitle) {
    return (new Newsletter())
        ->withSubject($newsletterTitle)
        ->withWelcomeTypeForSegment()
        ->create();
  }

  public function saveWelcomeNewsletterAsDraft(\AcceptanceTester $i) {
    $i->wantTo('save a welcome newsletter as a draft');
    $newsletterTitle = 'Save Welcome Email As Draft Test Email';
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->seeInCurrentUrl('#/new');
    $i->click('[data-automation-id="create_welcome"]');
    $i->waitForText('Welcome Email');
    $i->click('Next');
    $welcomeTemplate = $i->checkTemplateIsPresent(0, 'welcome');
    $i->waitForElement($welcomeTemplate);
    $i->see('Welcome Emails', ['css' => '.mailpoet-categories-item.active']);
    $i->click($welcomeTemplate);
    $i->waitForElement($this->titleElement);
    $i->fillField($this->titleElement, $newsletterTitle);
    $i->click('Next');
    $i->waitForText('Reply-to');
    $i->click('Save as draft and close');
    $i->waitForElement('[data-automation-id="newsletters_listing_tabs"]');
    $i->waitForText($newsletterTitle);
  }

  public function editWelcomeEmail(\AcceptanceTester $i) {
    $newsletter = $this->createWelcomeEmailWithTitle($i, 'Edit Welcome Email Test');
    $i->wantTo('Edit a welcome newsletter');
    $i->login();
    $i->amEditingNewsletter($newsletter->getId());
    $i->fillField($this->titleElement, 'Edit Test Welcome Edited');
    $i->click('Next');
    $i->waitForText('Reply-to');
    $i->click('Save as draft and close');
    $i->amOnMailpoetPage('Emails');
    $i->waitForElement('[data-automation-id="newsletters_listing_tabs"]');
    $i->click('Welcome Emails', '[data-automation-id="newsletters_listing_tabs"]');
    $i->waitForText('Edit Test Welcome Edited');
  }

  public function deleteWelcomeEmail(\AcceptanceTester $i) {
    $i->wantTo('Delete a welcome email');
    $newsletterTitle = 'Delete Welcome Email Test';
    $this->createWelcomeEmailWithTitle($i, $newsletterTitle);
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('Welcome Emails', '[data-automation-id="newsletters_listing_tabs"]');
    $i->waitForText($newsletterTitle);
    $i->clickItemRowActionByItemName($newsletterTitle, 'Move to trash');
    $i->changeGroupInListingFilter('trash');
    $i->waitForText($newsletterTitle);
    $i->clickItemRowActionByItemName($newsletterTitle, 'Restore');
    $i->amOnMailpoetPage('Emails');
    $i->click('Welcome Emails', '[data-automation-id="newsletters_listing_tabs"]');
    $i->waitForText($newsletterTitle);
  }

  public function duplicateWelcomeEmail (\AcceptanceTester $i) {
    $newsletterTitle = 'Duplicate Welcome Email Test';
    $this->createWelcomeEmailWithTitle($i, $newsletterTitle);
    $i->wantTo('Duplicate a welcome email');
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('Welcome Emails', '[data-automation-id="newsletters_listing_tabs"]');
    $i->waitForText($newsletterTitle);
    $i->clickItemRowActionByItemName($newsletterTitle, 'Duplicate');
    $i->waitForText('Copy of ' . $newsletterTitle);
  }

  public function searchForWelcomeEmail (\AcceptanceTester $i) {
    $i->wantTo('Search for a welcome email');
    $newsletterTitle = 'Welcome Email Search Test';
    $failureConditionNewsletter = 'Totes Fake';
    $this->createWelcomeEmailWithTitle($i, $newsletterTitle);
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('Welcome Emails', '[data-automation-id="newsletters_listing_tabs"]');
    $i->waitForText($newsletterTitle);
    $i->searchFor($failureConditionNewsletter);
    $i->waitForElement('tr.mailpoet-listing-no-items');
    $i->searchFor($newsletterTitle);
    $i->waitForText($newsletterTitle);
  }

  public function saveWelcomeEmailAsTemplate (\AcceptanceTester $i) {
    $i->wantTo('Save welcome email as a template');
    $templateTitle = 'Welcome Template Test Title';
    $newsletter = $this->createWelcomeEmailWithTitle($i, 'Save Welcome Email As Template Test');

    $saveTemplateOption = '[data-automation-id="newsletter_save_as_template_option"]';
    $saveTemplateButton = '[data-automation-id="newsletter_save_as_template_button"]';

    $i->login();
    $i->amEditingNewsletter($newsletter->getId());
    $i->click('[data-automation-id="newsletter_save_options_toggle"]');
    $i->waitForElement($saveTemplateOption);
    $i->click($saveTemplateOption);
    $i->waitForElement($saveTemplateButton);
    $i->fillField('template_name', $templateTitle);
    $i->click($saveTemplateButton);
    $i->waitForText('Template has been saved.');
    $i->amOnMailpoetPage('Emails');
    $i->click('[data-automation-id="new_email"]');
    $i->click('[data-automation-id="create_welcome"]');
    $i->click('Next');
    $i->checkTemplateIsPresent(0, 'welcome');
    $i->see('Welcome Emails', ['css' => '.mailpoet-categories-item.active']);
    $i->scrollTo('[data-automation-id="templates-welcome"]');
    $i->see($templateTitle);
    $i->click(['xpath' => '//*[text()="' . $templateTitle . '"]//ancestor::*[@data-automation-id="select_template_box"]//*[starts-with(@data-automation-id,"select_template_")]']);
    $i->waitForElement('[data-automation-id="newsletter_title"]');
    $i->seeNoJSErrors();
  }
}

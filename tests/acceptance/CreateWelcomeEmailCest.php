<?php

namespace MailPoet\Test\Acceptance;

class CreateWelcomeEmailCest {
  function createwelcomeNewsletter(\AcceptanceTester $I) {
    $I->wantTo('Create and configure welcome newsletter');
    $newsletter_title = 'Create Welcome Email';
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id=\'new_email\']');
    $I->seeInCurrentUrl('#/new');
    $I->click('[data-automation-id=\'create_welcome\']');
    $I->waitForText('Welcome Email', 20);
    $I->seeInCurrentUrl('mailpoet-newsletters#/new/welcome');
    $I->click('Next');
    $welcome_template = '[data-automation-id=\'select_template_0\']';
    $I->waitForElement($welcome_template, 20);
    $I->see('Welcome Emails', ['css' => 'a.current']);
    $I->seeInCurrentUrl('#/template');
    $I->click($welcome_template);
    $title_element = '[data-automation-id=\'newsletter_title\']';
    $I->waitForElement($title_element, 20);
    $I->seeInCurrentUrl('mailpoet-newsletter-editor');
    $I->fillField($title_element, $newsletter_title);
    $I->click('Next');
    $I->waitForText('Send this Welcome Email when', 30);
    ///$I->waitForElement('[data-automation-id="newsletter_send_form"]', 20);
    $I->click('Activate');
    $I->waitForElement('[data-automation-id="newsletters_listing_tabs"]', 20);
    $I->seeInCurrentUrl('mailpoet-newsletters');
  }
}
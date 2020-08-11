<?php

namespace MailPoet\Test\Acceptance;

class CreateWelcomeEmailCest {
  public function createWelcomeNewsletter(\AcceptanceTester $i) {
    $i->wantTo('Create and configure welcome newsletter');
    $newsletterTitle = 'Create Welcome Email';
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('[data-automation-id="create_welcome"]');
    $i->waitForText('Welcome Email');
    $i->click('Next');
    $welcomeTab = '[data-automation-id="templates-welcome"]';
    $i->waitForElement($welcomeTab);
    $i->click($welcomeTab);
    $welcomeTemplate = '[data-automation-id="select_template_0"]';
    $i->waitForElement($welcomeTemplate);
    $i->see('Welcome Emails', ['css' => 'a.current']);
    $i->click($welcomeTemplate);
    $titleElement = '[data-automation-id="newsletter_title"]';
    $i->waitForElement($titleElement);
    $i->fillField($titleElement, $newsletterTitle);
    $i->click('Next');
    $i->waitForText('Send this Welcome Email when');
    $i->click('Activate');
    $i->waitForElement('[data-automation-id="newsletters_listing_tabs"]');
    $i->searchFor($newsletterTitle);
    $i->waitForText($newsletterTitle);
    $i->seeNoJSErrors();
  }
}

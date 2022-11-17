<?php declare(strict_types = 1);

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
    $welcomeTemplate = $i->checkTemplateIsPresent(0, 'welcome');
    $i->see('Welcome Emails', ['css' => '.mailpoet-categories-item.active']);
    $i->click($welcomeTemplate);
    $titleElement = '[data-automation-id="newsletter_title"]';
    $i->waitForElement($titleElement);
    $i->fillField($titleElement, $newsletterTitle);
    $i->click('Next');
    $i->waitForText('When to send this welcome email?');
    $i->click('Activate');
    $i->waitForElement('[data-automation-id="newsletters_listing_tabs"]');
    $i->searchFor($newsletterTitle);
    $i->waitForText($newsletterTitle);
    $i->seeNoJSErrors();
  }
}

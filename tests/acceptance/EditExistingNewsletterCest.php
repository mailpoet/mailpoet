<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class EditExistingNewsletterCest {

  public function editExistingNewsletter(\AcceptanceTester $I) {
    $I->wantTo('Edit a standard newsletter');
    $newsletterTitle = 'Unedited Standard Title';
    $editedNewsletterTitle = "Edited Standard Title";
    $titleElement = '[data-automation-id="newsletter_title"]';
    $standardNewsletter = new Newsletter();
    $newsletter = $standardNewsletter->withSubject($newsletterTitle)->create();
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->waitForText($newsletterTitle);
    $I->clickItemRowActionByItemName($newsletterTitle, 'Edit');
    $I->waitForElement($titleElement);
    $I->fillField($titleElement, $editedNewsletterTitle);
    $I->click('Next');
    $I->waitForText('This subscriber segment');
    $I->click('Save as draft and close');
    $I->amOnMailpoetPage('Emails');
    $I->waitForText($editedNewsletterTitle);
  }
}

<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class EditExistingNewsletterCest {
  public function editExistingNewsletter(\AcceptanceTester $i) {
    $i->wantTo('Edit a standard newsletter');
    $newsletterTitle = 'Unedited Standard Title';
    $editedNewsletterTitle = "Edited Standard Title";
    $titleElement = '[data-automation-id="newsletter_title"]';
    $standardNewsletter = new Newsletter();
    $newsletter = $standardNewsletter->withSubject($newsletterTitle)->create();
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->waitForText($newsletterTitle);
    $i->clickItemRowActionByItemName($newsletterTitle, 'Edit');
    $i->waitForElement($titleElement);
    $i->fillField($titleElement, $editedNewsletterTitle);
    $i->click('Next');
    $i->waitForElement('[data-automation-id="newsletter_send_form"]');
    $i->waitForElementClickable('[data-automation-id="email-save-draft"]');
    $i->click('Save as draft and close');
    $i->amOnMailpoetPage('Emails');
    $i->waitForText($editedNewsletterTitle);
  }
}

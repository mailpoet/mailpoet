<?php

namespace MailPoet\Test\Acceptance;

class SaveNewsletterAsDraftCest {
  public function saveStandardNewsletterAsDraft(\AcceptanceTester $i) {
    $i->wantTo('Create standard newsletter and save as a draft');

    $newsletterTitle = 'Testing Newsletter ' . \MailPoet\Util\Security::generateRandomString();
    $segmentName = $i->createListWithSubscriber();

    $i->login();
    $i->amOnMailpoetPage('Emails');

    // step 1 - select notification type
    $i->click('[data-automation-id="create_standard"]');

    // step 2 - select template
    $standardTemplate = $i->checkTemplateIsPresent(0);
    $i->see('Newsletters', ['css' => '.mailpoet-categories-item.active']);
    $i->click($standardTemplate);

    // step 3 - design newsletter (update subject)
    $titleElement = '[data-automation-id="newsletter_title"]';
    $i->waitForElement($titleElement);
    $i->fillField($titleElement, $newsletterTitle);
    $i->click('Next');

    // step 4 - Choose list and send
    $sendFormElement = '[data-automation-id="newsletter_send_form"]';
    $i->waitForElement($sendFormElement);
    $i->selectOptionInSelect2($segmentName);
    $i->click('Save as draft and close');
    $i->waitForText($newsletterTitle);
  }
}

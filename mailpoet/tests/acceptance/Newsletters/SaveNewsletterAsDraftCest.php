<?php

namespace MailPoet\Test\Acceptance;

class SaveNewsletterAsDraftCest {
  public function saveStandardNewsletterAsDraft(\AcceptanceTester $i) {
    $i->wantTo('Create standard newsletter and save as a draft');
    $newsletterTitle = 'Testing Newsletter ' . \MailPoet\Util\Security::generateRandomString();

    $segmentName = $i->createListWithSubscriber();
    $inactiveNewsletterUI = '.mailpoet-listing-row-inactive';

    $this->startCreatingNewsletter($i, $newsletterTitle);

    // step 4 - Choose list and send
    $sendFormElement = '[data-automation-id="newsletter_send_form"]';
    $i->waitForElement($sendFormElement);
    $i->selectOptionInSelect2($segmentName);
    $i->waitForElementClickable('[data-automation-id="email-save-draft"]');
    $i->click('Save as draft and close');
    $i->waitForText($newsletterTitle);
    $this->assertNewsletterNotSent($i);
  }

  public function saveStandardNewsletterWithoutListAsDraft(\AcceptanceTester $i) {
    $i->wantTo('Create standard newsletter and save as a draft without selecting subscribers list');
    $newsletterTitle = 'Testing Newsletter ' . \MailPoet\Util\Security::generateRandomString();

    $this->startCreatingNewsletter($i, $newsletterTitle);

    // step 4 - Save as draft without selecting a list
    $i->waitForElementClickable('[data-automation-id="email-save-draft"]');
    $i->click('Save as draft and close');
    $i->waitForText($newsletterTitle);
    $this->assertNewsletterNotSent($i);
  }

  /**
   * Performs initial 3 steps for creating a newsletter
   */
  private function startCreatingNewsletter(\AcceptanceTester $i, string $title): void {

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
    $i->fillField($titleElement, $title);
    $i->click('Next');
  }

  private function assertNewsletterNotSent(\AcceptanceTester $i) {
    $inactiveNewsletterUI = '.mailpoet-listing-row-inactive';
    $i->waitForText('Not sent yet');
    $i->seeElement($inactiveNewsletterUI);
  }
}

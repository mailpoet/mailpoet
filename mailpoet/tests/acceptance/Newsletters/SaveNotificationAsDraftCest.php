<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class SaveNotificationAsDraftCest {
  public function saveNotificationAsDraft(\AcceptanceTester $i) {
    // step 1 - Prepare post notification data
    $i->wantTo('Save post notification email as draft');
    $inactiveNewsletterUI = '.mailpoet-listing-row-inactive';
    $newsletterTitle = 'Draft Test Post Notification';
    $newsletterFactory = new Newsletter();
    $newsletter = $newsletterFactory->withSubject($newsletterTitle)
      ->withPostNotificationsType()
      ->create();
    $segmentName = $i->createListWithSubscriber();
    // step 2 - Go to editor
    $i->login();
    $i->amEditingNewsletter($newsletter->getId());
    $i->click('Next');
    //Save Notification As Draft
    $sendFormElement = '[data-automation-id="newsletter_send_form"]';
    $saveAsDraftButton = '[data-automation-id="email-save-draft"]';
    $i->waitForElement($sendFormElement);
    $i->selectOptionInSelect2($segmentName);
    $i->scrollTo($saveAsDraftButton);
    $i->waitForElementVisible($saveAsDraftButton);
    $i->click($saveAsDraftButton);
    $i->waitForElement('[data-automation-id="newsletters_listing_tabs"]');
    $i->waitForText($newsletterTitle);
    $i->dontSeeCheckboxIsChecked('.mailpoet-form-toggle input[type="checkbox"]');
    $i->waitForText('Not sent yet');
    $i->seeElement($inactiveNewsletterUI);
  }
}

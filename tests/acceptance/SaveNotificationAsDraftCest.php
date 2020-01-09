<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class SaveNotificationAsDraftCest {

  public function saveNotificationAsDraft(\AcceptanceTester $i) {
    // step 1 - Prepare post notification data
    $i->wantTo('Save post notification email as draft');
    $newsletterTitle = 'Draft Test Post Notification';
    $newsletterFactory = new Newsletter();
    $newsletter = $newsletterFactory->withSubject($newsletterTitle)
      ->withPostNotificationsType()
      ->create();
    $segmentName = $i->createListWithSubscriber();
    // step 2 - Go to editor
    $i->login();
    $i->amEditingNewsletter($newsletter->id);
    $i->click('Next');
    //Save Notification As Draft
    $sendFormElement = '[data-automation-id="newsletter_send_form"]';
    $i->waitForElement($sendFormElement);
    $i->selectOptionInSelect2($segmentName);
    $i->click('Save as draft and close');
    $i->waitForElement('[data-automation-id="newsletters_listing_tabs"]');
    $i->waitForText('Draft Test Post Notification');
  }

}

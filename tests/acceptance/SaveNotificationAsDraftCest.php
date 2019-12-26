<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class SaveNotificationAsDraftCest {

  public function saveNotificationAsDraft(\AcceptanceTester $I) {
    // step 1 - Prepare post notification data
    $I->wantTo('Save post notification email as draft');
    $newsletter_title = 'Draft Test Post Notification';
    $newsletterFactory = new Newsletter();
    $newsletter = $newsletterFactory->withSubject($newsletter_title)
      ->withPostNotificationsType()
      ->create();
    $segment_name = $I->createListWithSubscriber();
    // step 2 - Go to editor
    $I->login();
    $I->amEditingNewsletter($newsletter->id);
    $I->click('Next');
    //Save Notification As Draft
    $send_form_element = '[data-automation-id="newsletter_send_form"]';
    $I->waitForElement($send_form_element);
    $I->selectOptionInSelect2($segment_name);
    $I->click('Save as draft and close');
    $I->waitForElement('[data-automation-id="newsletters_listing_tabs"]');
    $I->waitForText('Draft Test Post Notification');
  }

}

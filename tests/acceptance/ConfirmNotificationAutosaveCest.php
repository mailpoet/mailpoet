<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class ConfirmNotificationAutosaveCest {

  public function confirmNotificationAutosave(\AcceptanceTester $I) {
    $I->wantTo('Confirm Post Notification Autosave');
    $newsletter_title = 'Notification Autosave Test';
    // step 1 - Prepare post notification data
    $newsletter = new Newsletter();
    $newsletter = $newsletter->withSubject($newsletter_title)
      ->withPostNotificationsType()
      ->create();
    // step 2 - Go to editor
    $I->login();
    $I->amEditingNewsletter($newsletter->id);
    // step 3 - Add subject, wait for Autosave
    $title_element = '[data-automation-id="newsletter_title"]';
    $I->waitForElement($title_element);
    $I->fillField($title_element, $newsletter_title);
    $I->waitForText('Autosaved');
  }

}

<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class ConfirmNotificationAutosaveCest {
  public function confirmNotificationAutosave(\AcceptanceTester $i) {
    $i->wantTo('Confirm Post Notification Autosave');
    $newsletterTitle = 'Notification Autosave Test';
    // step 1 - Prepare post notification data
    $newsletter = new Newsletter();
    $newsletter = $newsletter->withSubject($newsletterTitle)
      ->withPostNotificationsType()
      ->create();
    // step 2 - Go to editor
    $i->login();
    $i->amEditingNewsletter($newsletter->getId());
    // step 3 - Add subject, wait for Autosave
    $titleElement = '[data-automation-id="newsletter_title"]';
    $i->waitForElement($titleElement);
    $i->fillField($titleElement, $newsletterTitle);
    $i->waitForText('Autosaved');
  }
}

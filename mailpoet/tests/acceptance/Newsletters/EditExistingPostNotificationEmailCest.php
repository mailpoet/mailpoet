<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class EditExistingPostNotificationEmailCest {
  public function editSubjectAndSchedule(\AcceptanceTester $i) {
    $i->wantTo('Edit existing post notification email');

    $newsletterTitle = 'Edit Test Post Notification';
    $newsletterEditedTitle = 'Edit Test Post Notification Edited';

    // step 1 - Prepare post notification data
    $form = new Newsletter();
    $newsletter = $form->withSubject($newsletterTitle)
      ->withPostNotificationsType()
      ->create();
    $segmentName = $i->createListWithSubscriber();

    // step 2 - Open list of post notifications
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('Post Notifications', '[data-automation-id="newsletters_listing_tabs"]');

    // step 3 - Open editation of post notifcation newsletter
    $listingAutomationSelector = '[data-automation-id="listing_item_' . $newsletter->getId() . '"]';
    $i->waitForText('Edit Test Post Notification', 10, $listingAutomationSelector);
    $i->clickItemRowActionByItemName($newsletterTitle, 'Edit');

    // step 4 - Edit subject
    $titleElement = '[data-automation-id="newsletter_title"]';
    $i->waitForElement($titleElement);
    $i->fillField($titleElement, $newsletterEditedTitle);

    // step 5 - Change schedule, list and activate
    $i->click('Next');
    $i->waitForElement('textarea.select2-search__field');
    $i->selectOption('[data-automation-id="newsletter_interval_type"]', 'Weekly on...');
    $i->selectOptionInSelect2($segmentName);
    $newsletterListingElement = '[data-automation-id="listing_item_' . basename($i->getCurrentUrl()) . '"]';
    $i->click('Activate');
    $i->waitForElement($newsletterListingElement);
    $i->see($newsletterEditedTitle, $newsletterListingElement);
  }
}

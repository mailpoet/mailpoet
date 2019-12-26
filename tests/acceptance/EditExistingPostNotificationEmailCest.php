<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class EditExistingPostNotificationEmailCest {

  public function editSubjectAndSchedule(\AcceptanceTester $I) {
    $I->wantTo('Edit existing post notification email');

    $newsletter_title = 'Edit Test Post Notification';
    $newsletter_edited_title = 'Edit Test Post Notification Edited';

    // step 1 - Prepare post notification data
    $form = new Newsletter();
    $newsletter = $form->withSubject($newsletter_title)
      ->withPostNotificationsType()
      ->create();
    $segment_name = $I->createListWithSubscriber();

    // step 2 - Open list of post notifications
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('Post Notifications', '[data-automation-id="newsletters_listing_tabs"]');

    // step 3 - Open editation of post notifcation newsletter
    $listing_automation_selector = '[data-automation-id="listing_item_' . $newsletter->id . '"]';
    $I->waitForText('Edit Test Post Notification', 10, $listing_automation_selector);
    $I->clickItemRowActionByItemName($newsletter_title, 'Edit');

    // step 4 - Edit subject
    $title_element = '[data-automation-id="newsletter_title"]';
    $I->waitForElement($title_element);
    $I->fillField($title_element, $newsletter_edited_title);

    // step 5 - Change schedule, list and activate
    $I->click('Next');
    $I->waitForElement('input.select2-search__field');
    $I->selectOption('[data-automation-id="newsletter_interval_type"]', 'Weekly on...');
    $I->selectOptionInSelect2($segment_name);
    $newsletter_listing_element = '[data-automation-id="listing_item_' . basename($I->getCurrentUrl()) . '"]';
    $I->click('Activate');
    $I->waitForElement($newsletter_listing_element);
    $I->see($newsletter_edited_title, $newsletter_listing_element);
  }

}

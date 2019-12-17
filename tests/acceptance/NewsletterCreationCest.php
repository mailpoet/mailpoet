<?php

namespace MailPoet\Test\Acceptance;

class NewsletterCreationCest {

  function createPostNotification(\AcceptanceTester $I) {
    $I->wantTo('Create and configure post notification email');

    $newsletter_title = 'Post Notification ' . \MailPoet\Util\Security::generateRandomString();
    $segment_name = $I->createListWithSubscriber();

    $I->login();
    $I->amOnMailpoetPage('Emails');

    // step 1 - select notification type
    $I->click('[data-automation-id="create_notification"]');

    // step 2 - configure schedule
    $I->waitForText('Latest Post Notifications');
    $I->selectOption('select[name=intervalType]', 'immediately');
    $I->click('Next');

    // step 3 - select template
    $post_notification_template = '[data-automation-id="select_template_2"]';
    $I->waitForElement($post_notification_template);
    $I->see('Post Notifications', ['css' => 'a.current']);
    $I->click($post_notification_template);

    // step 4 - design newsletter (update subject)
    $title_element = '[data-automation-id="newsletter_title"]';
    $I->waitForElement($title_element);
    $I->fillField($title_element, $newsletter_title);
    $I->click('Next');

    // step 5 - activate
    $search_field_element = 'input.select2-search__field';
    $I->waitForElement($search_field_element);
    $I->see('Select a frequency');
    $newsletter_listing_element = '[data-automation-id="listing_item_' . basename($I->getCurrentUrl()) . '"]';
    $I->selectOptionInSelect2($segment_name);
    $I->click('Activate');
    $I->waitForElement($newsletter_listing_element);
    $I->see($newsletter_title, $newsletter_listing_element);
    $I->see("Send immediately if there's new content to " . $segment_name . ".", $newsletter_listing_element);
  }

  function createStandardNewsletter(\AcceptanceTester $I) {
    $I->wantTo('Create and configure standard newsletter');

    $newsletter_title = 'Testing Newsletter ' . \MailPoet\Util\Security::generateRandomString();
    $segment_name = $I->createListWithSubscriber();

    $I->login();
    $I->amOnMailpoetPage('Emails');

    // step 1 - select notification type
    $I->click('[data-automation-id="create_standard"]');

    // step 2 - select template
    $standard_template = '[data-automation-id="select_template_0"]';
    $I->waitForElement($standard_template);
    $I->see('Newsletters', ['css' => 'a.current']);
    $I->click($standard_template);

    // step 3 - design newsletter (update subject)
    $title_element = '[data-automation-id="newsletter_title"]';
    $I->waitForElement($title_element);
    $I->fillField($title_element, $newsletter_title);
    $I->click('Next');

    // step 4 - Choose list and send
    $send_form_element = '[data-automation-id="newsletter_send_form"]';
    $I->waitForElement($send_form_element);
    $I->selectOptionInSelect2($segment_name);
    $I->click('Send');
  }
}

<?php

namespace MailPoet\Test\Acceptance;

class NewsletterCreationCest {
  function createPostNotification(\AcceptanceTester $I) {
    $I->wantTo('Create and configure post notification email');

    $newsletter_title = 'Post Notification ' . \MailPoet\Util\Security::generateRandomString();

    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id=\'new_email\']');

    // step 1 - select notification type
    $I->seeInCurrentUrl('#/new');
    $I->click('[data-automation-id=\'create_notification\']');

    // step 2 - configure schedule
    $I->waitForText('Latest Post Notifications');
    $I->seeInCurrentUrl('#/new/notification');
    $I->selectOption('select[name=intervalType]', 'immediately');
    $I->click('Next');

    // step 3 - select template
    $post_notification_template = '[data-automation-id=\'select_template_0\']';
    $I->waitForElement($post_notification_template);
    $I->see('Post Notifications', ['css' => 'a.current']);
    $I->seeInCurrentUrl('#/template');
    $I->click($post_notification_template);

    // step 3 - design newsletter (update subject)
    $title_element = '[data-automation-id=\'newsletter_title\']';
    $I->waitForElement($title_element);
    $I->seeInCurrentUrl('mailpoet-newsletter-editor');
    $I->fillField($title_element, $newsletter_title);
    $I->click('Next');

    // step 4 - activate
    $search_field_element = 'input.select2-search__field';
    $I->waitForElement($search_field_element);
    $I->seeInCurrentUrl('#/send');
    $I->see('Select a frequency');
    $newsletter_listing_element = '[data-automation-id="listing_item_' . basename($I->getCurrentUrl()) . '"]';
    $I->fillField($search_field_element, 'WordPress Users');
    $I->pressKey($search_field_element, \WebDriverKeys::ENTER);
    $I->click('Activate');
    $I->waitForElement($newsletter_listing_element);
    $I->see($newsletter_title, $newsletter_listing_element);
    $I->see("Send immediately if there's new content to WordPress Users.", $newsletter_listing_element);
    $I->wait(20);
  }
}
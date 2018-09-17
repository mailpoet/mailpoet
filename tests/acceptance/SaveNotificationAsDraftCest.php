<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

require_once __DIR__ . '/../DataFactories/Newsletter.php';

class SaveNotificationAsDraftCest  {
  function saveNotificationAsDraft(\AcceptanceTester $I) {
    // step 1 - Prepare post notification data
    $I->wantTo('Save post notification email as draft');
    $newsletter_title = 'Draft Test Post Notification';
    $newsletterFactory = new Newsletter();
    $newsletter = $newsletterFactory->withSubject($newsletter_title)
      ->withType('notification')
      ->withPostNoticationOptions()
      ->create();
    // step 2 - Go to editor
    $I->login();
    $I->amEditingNewsletter($newsletter->id);
    $I->click('Next');
    //Save Notification As Draft
    $send_form_element = '[data-automation-id="newsletter_send_form"]';
    $I->waitForElement($send_form_element);
    $I->seeInCurrentUrl('mailpoet-newsletters#/send/');
    $search_field_element = 'input.select2-search__field';
    $I->fillField($search_field_element, 'WordPress Users');
    $I->pressKey($search_field_element, \WebDriverKeys::ENTER);
    $I->click('Save as draft and close');
    $I->waitForElement('[data-automation-id="newsletters_listing_tabs"]', 10);
    $I->seeInCurrentUrl('/wp-admin/admin.php?page=mailpoet-newsletters#/notification');
    $I->waitForText('Draft Test Post Notification');
  }
}

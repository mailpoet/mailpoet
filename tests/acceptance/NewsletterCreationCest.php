<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Scenario;
use MailPoet\Test\DataFactories\Settings;

class NewsletterCreationCest {
  public function createPostNotification(\AcceptanceTester $i) {
    $i->wantTo('Create and configure post notification email');

    $newsletterTitle = 'Post Notification ' . \MailPoet\Util\Security::generateRandomString();
    $segmentName = $i->createListWithSubscriber();

    $i->login();
    $i->amOnMailpoetPage('Emails');

    // step 1 - select notification type
    $i->click('[data-automation-id="create_notification"]');

    // step 2 - configure schedule
    $i->waitForText('Latest Post Notifications');
    $i->selectOption('select[name=intervalType]', 'immediately');
    $i->click('Next');

    // step 3 - select template
    $templateTab = '[data-automation-id="templates-notification"]';
    $i->waitForElement($templateTab);
    $i->click($templateTab);
    $postNotificationTemplate = '[data-automation-id="select_template_1"]';
    $i->waitForElement($postNotificationTemplate);
    $i->see('Post Notifications', ['css' => 'a.current']);
    $i->click($postNotificationTemplate);

    // step 4 - design newsletter (update subject)
    $titleElement = '[data-automation-id="newsletter_title"]';
    $i->waitForElement($titleElement);
    $i->fillField($titleElement, $newsletterTitle);
    $i->click('Next');

    // step 5 - activate
    $searchFieldElement = 'input.select2-search__field';
    $i->waitForElement($searchFieldElement);
    $i->see('Select a frequency');
    $newsletterListingElement = '[data-automation-id="listing_item_' . basename($i->getCurrentUrl()) . '"]';
    $i->selectOptionInSelect2($segmentName);
    $i->click('Activate');
    $i->waitForElement($newsletterListingElement);
    $i->see($newsletterTitle, $newsletterListingElement);
    $i->see("Send immediately if there's new content to " . $segmentName . ".", $newsletterListingElement);
  }

  public function createStandardNewsletter(\AcceptanceTester $i) {
    $i->wantTo('Create and configure standard newsletter');

    $newsletterTitle = 'Testing Newsletter ' . \MailPoet\Util\Security::generateRandomString();
    $segmentName = $i->createListWithSubscriber();

    $i->login();
    $i->amOnMailpoetPage('Emails');

    // step 1 - select notification type
    $i->click('[data-automation-id="create_standard"]');

    // step 2 - select template
    $templateTab = '[data-automation-id="templates-standard"]';
    $i->waitForElement($templateTab);
    $i->click($templateTab);
    $standardTemplate = '[data-automation-id="select_template_0"]';
    $i->waitForElement($standardTemplate);
    $i->see('Newsletters', ['css' => 'a.current']);
    $i->click($standardTemplate);

    // step 3 - design newsletter (update subject)
    $titleElement = '[data-automation-id="newsletter_title"]';
    $i->waitForElement($titleElement);
    $i->fillField($titleElement, $newsletterTitle);
    $i->click('Next');

    // step 4 - Choose list and send
    $sendFormElement = '[data-automation-id="newsletter_send_form"]';
    $i->waitForElement($sendFormElement);
    $i->selectOptionInSelect2($segmentName);
    $i->click('Send');
  }

  public function createNewsletterWhenKeyPendingApproval(\AcceptanceTester $i, Scenario $scenario) {
    $mailPoetSendingKey = getenv('WP_TEST_MAILER_MAILPOET_API');
    if (!getenv('WP_TEST_MAILER_MAILPOET_API')) {
      $scenario->skip("Skipping, 'WP_TEST_MAILER_MAILPOET_API' not set.");
    }
    $settings = new Settings();
    $settings->withSendingMethodMailPoet();
    $settings->withMssKeyPendingApproval();

    $i->createListWithSubscriber();

    $i->login();
    $i->amOnMailpoetPage('Emails');

    // step 1 - select type
    $i->click('[data-automation-id="create_standard"]');

    // step 2 - select template
    $templateTab = '[data-automation-id="templates-standard"]';
    $i->waitForElement($templateTab);
    $i->click($templateTab);
    $standardTemplate = '[data-automation-id="select_template_0"]';
    $i->waitForElement($standardTemplate);
    $i->see('Newsletters', ['css' => 'a.current']);
    $i->click($standardTemplate);

    // step 3 - see notice in 'Send preview' with link to authorized emails
    $i->waitForElement('[data-automation-id="sidebar_preview_region_heading"]');
    $i->click('[data-automation-id="sidebar_preview_region_heading"]', '#mailpoet_editor_sidebar');
    $i->waitForText('You’ll soon be able to send once our team reviews your account. In the meantime, you can send previews to your authorized emails.');
    $href = $i->grabAttributeFrom('//a[text()="your authorized emails"]', 'href');
    expect($href)->same('https://account.mailpoet.com/authorization');
    $i->scrollToTop();
    $i->click('Next');

    // step 4 - see notice in 'Send preview' with link to authorized emails, 'Send' button must be disabled
    $i->waitForText('Final Step');
    $i->waitForText('You’ll soon be able to send once our team reviews your account. In the meantime, you can send previews to your authorized emails.');
    $href = $i->grabAttributeFrom('//a[text()="your authorized emails"]', 'href');
    $disabled = $i->grabAttributeFrom('input[value="Send"]', 'disabled');
    expect($href)->same('https://account.mailpoet.com/authorization');
    expect($disabled)->same('true');
  }
}

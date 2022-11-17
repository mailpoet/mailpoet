<?php declare(strict_types = 1);

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
    $i->waitForElement('[data-automation-id="post_notification_creation_heading"]');
    $i->selectOption('select[name=intervalType]', 'immediately');
    $i->click('Next');

    // step 3 - select template
    $postNotificationTemplate = $i->checkTemplateIsPresent(1, 'notification');
    $i->see('Post Notifications', ['css' => '.mailpoet-categories-item.active']);
    $i->click($postNotificationTemplate);

    // step 4 - design newsletter (update subject)
    $titleElement = '[data-automation-id="newsletter_title"]';
    $i->waitForElement($titleElement);
    $i->fillField($titleElement, $newsletterTitle);
    $i->click('Next');

    // step 5 - activate
    $searchFieldElement = 'textarea.select2-search__field';
    $i->waitForElement($searchFieldElement);
    $i->see('Select a frequency');
    $newsletterListingElement = '[data-automation-id="listing_item_' . basename($i->getCurrentUrl()) . '"]';
    $i->selectOptionInSelect2($segmentName);
    $i->click('Activate');
    $i->waitForElement($newsletterListingElement);
    $i->see($newsletterTitle, $newsletterListingElement);
    $i->see('Immediately', $newsletterListingElement);
    $i->see('Send to ' . $segmentName, $newsletterListingElement);
  }

  public function createStandardNewsletter(\AcceptanceTester $i) {
    $i->wantTo('Create and configure standard newsletter');

    $newsletterTitle = 'Testing Newsletter ' . \MailPoet\Util\Security::generateRandomString();
    $segmentName = $i->createListWithSubscriber();

    $i->login();
    $i->amOnMailpoetPage('Emails');

    // step 1 - select newsletter type
    $i->click('[data-automation-id="create_standard"]');

    // step 2 - select template
    $standardTemplate = $i->checkTemplateIsPresent(0);
    $i->see('Newsletters', ['css' => '.mailpoet-categories-item.active']);
    $i->click($standardTemplate);

    // step 3 - design newsletter (update subject)
    $titleElement = '[data-automation-id="newsletter_title"]';
    $i->waitForElement($titleElement);
    $i->fillField($titleElement, $newsletterTitle);
    $i->click('Next');

    // step 4 - choose list and send
    $sendFormElement = '[data-automation-id="newsletter_send_form"]';
    $i->waitForElement($sendFormElement);
    $i->selectOptionInSelect2($segmentName);
    $i->click('Send');

    // step 5 - verify recently sent newsletter tab and sent newsletter
    $niceJobText = 'Nice job! Check back in 6 hour(s) for more stats.';
    $i->waitForText('Emails');
    $i->waitForText('The newsletter is being sent...');
    $i->reloadPage();
    $i->click('[data-automation-id="new_email"]');
    $i->click('[data-automation-id="create_standard"]');
    $i->waitForElement('[data-automation-id="email_template_selection_heading"]');
    $i->see('Recently sent', ['css' => '.mailpoet-categories-item.active']);
    $i->click($standardTemplate);
    $i->waitForElement($titleElement);
    $i->fillField($titleElement, $newsletterTitle);
    $i->click('Next');
    $i->waitForElement($sendFormElement);
    $i->selectOptionInSelect2($segmentName);
    $i->click('Send');
    $i->waitForText('Newsletters');
    $i->waitForText($niceJobText);
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
    $standardTemplate = $i->checkTemplateIsPresent(0);
    $i->see('Newsletters', ['css' => '.mailpoet-categories-item.active']);
    $i->click($standardTemplate);

    // step 3 - see notice in 'Send preview' with link to authorized emails
    $i->waitForElement('.mailpoet_show_preview');
    $i->click('.mailpoet_show_preview');
    $i->waitForElement('[data-automation-id="switch_send_to_email"]');
    $i->click('[data-automation-id="switch_send_to_email"]');
    $i->waitForText('You’ll soon be able to send once our team reviews your account. In the meantime, you can send previews to your authorized emails.');
    $href = $i->grabAttributeFrom('//a[text()="your authorized emails"]', 'href');
    expect($href)->same('https://account.mailpoet.com/authorization');
    $i->click('#mailpoet_modal_close');
    $i->scrollToTop();
    $i->click('Next');

    // step 4 - see notice in 'Send preview' with link to authorized emails, 'Send' button must be disabled
    $i->waitForElement('[data-automation-id="newsletter_send_heading"]');
    $i->waitForText('You’ll soon be able to send once our team reviews your account. In the meantime, you can send previews to your authorized emails.');
    $href = $i->grabAttributeFrom('//a[text()="your authorized emails"]', 'href');
    expect($href)->same('https://account.mailpoet.com/authorization');
    $i->seeElement('[data-automation-id="email-submit"]:disabled');
  }
}

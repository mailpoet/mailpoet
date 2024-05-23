<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Test\DataFactories\Subscriber;

/**
 * @group frontend
 */
class SubscriptionPageCest {
  public function previewDefaultSubscriptionPage(\AcceptanceTester $i) {
    $i->wantTo('Preview default MailPoet page from MP Settings page');

    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="preview_manage_subscription_page_link"]');
    $i->switchToNextTab();
    $i->waitForText('Manage your subscription');
  }

  public function createNewSubscriptionPage(\AcceptanceTester $i) {
    $i->wantTo('Make and set a custom manage subscription page');

    $pageTitle = 'CustomSubscriptionPage';
    $pageContent = 'This is custom manage subscription page';
    $emailContent = 'Click <a href="[link:subscription_manage_url]">Manage Subscription</a> to see the subscription page.';
    $emailAddress = 'subscriber@example.com';
    $confirmationEmailName = 'Confirm your subscription';
    $subscriberFactory = new Subscriber();
    $subscriberFactory->withEmail($emailAddress)
      ->withStatus('unconfirmed')
      ->withCountConfirmations(0)
      ->create();

    $i->cli(['post', 'create', '--post_status=publish', '--post_type=page', "--post_title='$pageTitle'", "--post_content='$pageContent [mailpoet_manage_subscription]'"]);

    $i->login();

    $i->amOnMailPoetPage('Settings');
    $i->click(['css' => '[data-automation-id="subscription-manage-page-selection"]']);
    $i->selectOption('[data-automation-id="subscription-manage-page-selection"]', $pageTitle);
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->waitForNoticeAndClose('Settings saved');

    $i->wantTo('Click the manage subscription link and verify it');

    // Making a shortcut in this scenario by providing required url in the conf email
    $i->click('[data-automation-id="signup_settings_tab"]');
    $i->checkOption('[data-automation-id="mailpoet_confirmation_email_customizer"]');
    $i->clearField('[data-automation-id="signup_confirmation_email_body"]');
    $i->fillField('[data-automation-id="signup_confirmation_email_body"]', $emailContent);
    $i->click('Save settings');
    $i->waitForText('Settings saved');

    $i->amOnMailpoetPage('Subscribers');
    $i->waitForText($emailAddress);
    $i->clickItemRowActionByItemName($emailAddress, 'Resend confirmation email');
    $i->waitForText('1 confirmation email has been sent.');

    $i->amOnMailboxAppPage();
    $i->checkEmailWasReceived($confirmationEmailName);
    $i->click(Locator::contains('span.subject', $confirmationEmailName));
    $i->switchToIframe('#preview-html');
    $i->click('Manage Subscription');
    $i->switchToNextTab();
    $i->waitForText($pageTitle);
    $i->waitForText($pageContent);
  }
}

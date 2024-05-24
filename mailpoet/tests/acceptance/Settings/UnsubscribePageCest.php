<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Test\DataFactories\Subscriber;

/**
 * @group frontend
 */
class UnsubscribePageCest {

  /** @var string */
  private $emailAddress;
  private $pageTitleConfirmation;
  private $pageTitleSuccess;
  private $pageContent;
  private $emailContent;
  private $confirmationEmailName;

  public function _before(\AcceptanceTester $i) {
    $this->emailAddress = 'subscriber@example.com';
    $this->pageTitleConfirmation = 'Custom Unsubscribe Confirmation Page';
    $this->pageTitleSuccess = 'Custom Unsubscribe Success Page';
    $this->pageContent = 'This is custom unsubscribe page';
    $this->emailContent = 'Click <a href="[link:subscription_unsubscribe_url]">Unsubscribe</a> to get unsubscribed.';
    $this->confirmationEmailName = 'Confirm your subscription';
    $subscriberFactory = new Subscriber();
    $subscriberFactory->withEmail($this->emailAddress)
      ->withStatus('unconfirmed')
      ->withCountConfirmations(0)
      ->create();

    $i->cli(['post', 'create', '--post_status=publish', '--post_type=page', "--post_title='$this->pageTitleConfirmation'", "--post_content='$this->pageContent [mailpoet_page]'"]);
    $i->cli(['post', 'create', '--post_status=publish', '--post_type=page', "--post_title='$this->pageTitleSuccess'", "--post_content='$this->pageContent [mailpoet_page]'"]);
  }

  public function previewDefaultUnsubscribePage(\AcceptanceTester $i) {
    $i->wantTo('Preview default MailPoet Unsubscribe page from MP Settings page');

    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="unsubscribe_page_preview_link"]');
    $i->switchToNextTab();
    $i->waitForText('You are now unsubscribed.');
  }

  public function createNewUnsubscribeConfirmationPage(\AcceptanceTester $i) {
    $i->wantTo('Make a custom unsubscribe confirmation page');

    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->scrollTo('[data-automation-id="subscription-manage-page-selection"]');
    $i->click(['css' => '[data-automation-id="unsubscribe-confirmation-page-selection"]']);
    $i->selectOption('[data-automation-id="unsubscribe-confirmation-page-selection"]', $this->pageTitleConfirmation);
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->waitForNoticeAndClose('Settings saved');

    $i->wantTo('Click the Unsubscribe link and verify the Confirmation page');
    
    // Making a shortcut in this scenario by providing required url in the conf email
    $i->click('[data-automation-id="signup_settings_tab"]');
    $i->checkOption('[data-automation-id="mailpoet_confirmation_email_customizer"]');
    $i->clearField('[data-automation-id="signup_confirmation_email_body"]');
    $i->fillField('[data-automation-id="signup_confirmation_email_body"]', $this->emailContent);
    $i->click('Save settings');
    $i->waitForText('Settings saved');

    $i->amOnMailpoetPage('Subscribers');
    $i->waitForText($this->emailAddress);
    $i->clickItemRowActionByItemName($this->emailAddress, 'Resend confirmation email');
    $i->waitForText('1 confirmation email has been sent.');

    $i->amOnMailboxAppPage();
    $i->checkEmailWasReceived($this->confirmationEmailName);
    $i->click(Locator::contains('span.subject', $this->confirmationEmailName));
    $i->switchToIframe('#preview-html');
    $i->click('Unsubscribe');
    $i->switchToNextTab();
    $i->waitForText($this->pageTitleConfirmation);
    $i->waitForText($this->pageContent);
  }

  public function createNewUnsubscribeSuccessPage(\AcceptanceTester $i) {
    $i->wantTo('Make a custom unsubscribe success page');

    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->scrollTo('[data-automation-id="subscription-manage-page-selection"]');
    $i->click(['css' => '[data-automation-id="unsubscribe-success-page-selection"]']);
    $i->selectOption('[data-automation-id="unsubscribe-success-page-selection"]', $this->pageTitleSuccess);
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->waitForNoticeAndClose('Settings saved');

    $i->wantTo('Click the Unsubscribe link and verify the Success page');

    // Making a shortcut in this scenario by providing required url in the conf email
    $i->click('[data-automation-id="signup_settings_tab"]');
    $i->checkOption('[data-automation-id="mailpoet_confirmation_email_customizer"]');
    $i->clearField('[data-automation-id="signup_confirmation_email_body"]');
    $i->fillField('[data-automation-id="signup_confirmation_email_body"]', $this->emailContent);
    $i->click('Save settings');
    $i->waitForText('Settings saved');

    $i->amOnMailpoetPage('Subscribers');
    $i->waitForText($this->emailAddress);
    $i->clickItemRowActionByItemName($this->emailAddress, 'Resend confirmation email');
    $i->waitForText('1 confirmation email has been sent.');

    $i->amOnMailboxAppPage();
    $i->checkEmailWasReceived($this->confirmationEmailName);
    $i->click(Locator::contains('span.subject', $this->confirmationEmailName));
    $i->switchToIframe('#preview-html');
    $i->click('Unsubscribe');
    $i->switchToNextTab();
    $i->waitForText('Yes, unsubscribe me');
    $i->click('Yes, unsubscribe me');
    $i->waitForText($this->pageTitleSuccess);
  }
}

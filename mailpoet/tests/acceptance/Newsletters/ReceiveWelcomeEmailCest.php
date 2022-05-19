<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\Subscriber;

class ReceiveWelcomeEmailCest {

  /** @var Settings */
  private $settings;

  public function _before() {
    $this->settings = new Settings();
  }

  public function receiveWelcomeEmail(\AcceptanceTester $i) {
    $i->wantTo('Receive a welcome email as a subscriber');
    $this->settings->withCronTriggerMethod('Action Scheduler');
    $segmentName = 'Receive Welcome Email List';
    $segmentFactory = new Segment();
    $subscribersList = $segmentFactory->withName($segmentName)->create();
    $emailAddress = 'welcomeuser@example.com';
    $subscriberFactory = new Subscriber();
    $subscriberFactory->withEmail($emailAddress)
      ->withStatus('unconfirmed')
      ->withSegments([$subscribersList])
      ->withCountConfirmations(0)
      ->create();
    $welcomeNewsletterName = 'Welcome Email Test';
    $confirmationEmailName = 'Confirm your subscription';
    $newsletterFactory = new Newsletter();
    $newsletterFactory->withSubject($welcomeNewsletterName)
      ->withWelcomeTypeForSegment($subscribersList->getId())
      ->withActiveStatus()
      ->withSegments([$subscribersList])
      ->create();

    $i->login();
    // go and resend confirmation email
    $i->amOnMailpoetPage('Subscribers');
    $i->waitForListingItemsToLoad();
    $i->clickItemRowActionByItemName($emailAddress, 'Resend confirmation email');
    $i->waitForText('1 confirmation email has been sent.');
    // confirm subscription
    $i->checkEmailWasReceived($confirmationEmailName);
    $i->click(Locator::contains('span.subject', $confirmationEmailName));
    $i->switchToIframe('#preview-html');
    $i->click('I confirm my subscription!');
    $i->switchToNextTab();
    $i->reloadPage();
    // check for welcome email
    $i->checkEmailWasReceived($welcomeNewsletterName);
    $i->click(Locator::contains('span.subject', $welcomeNewsletterName));
  }
}

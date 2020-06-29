<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Test\DataFactories\Subscriber;

class SubscribersListingCest {
  public function subscribersListing(\AcceptanceTester $i) {
    $i->wantTo('Open subscribers listings page');

    (new Subscriber())
      ->withEmail('wp@example.com')
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Subscribers');
    $i->searchFor('wp@example.com');
    $i->waitForText('wp@example.com');
  }

  public function sendConfirmationEmail(\AcceptanceTester $i) {
    $i->wantTo('Send confirmation email');

    $disallowedEmail = 'disallowed@example.com';
    $allowedEmail = 'allowed@example.com';

    $subscriberResendDisallowed = (new Subscriber())
      ->withEmail($disallowedEmail)
      ->withStatus('unconfirmed')
      ->withCountConfirmations(ConfirmationEmailMailer::MAX_CONFIRMATION_EMAILS)
      ->create();

    $subscriberResendAllowed = (new Subscriber())
      ->withEmail($allowedEmail)
      ->withStatus('unconfirmed')
      ->withCountConfirmations(0)
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Subscribers');

    $i->moveMouseOver(['xpath' => '//*[text()="' . $disallowedEmail . '"]//ancestor::tr']);
    $i->dontSee('Resend confirmation email', '//*[text()="' . $disallowedEmail . '"]//ancestor::tr');

    $i->clickItemRowActionByItemName($allowedEmail, 'Resend confirmation email');
    $i->waitForText('1 confirmation email has been sent.');

    $i->checkEmailWasReceived('Confirm your subscription');
  }
}

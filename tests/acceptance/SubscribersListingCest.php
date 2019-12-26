<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Test\DataFactories\Subscriber;

class SubscribersListingCest {

  public function subscribersListing(\AcceptanceTester $I) {
    $I->wantTo('Open subscribers listings page');

    (new Subscriber())
      ->withEmail('wp@example.com')
      ->create();

    $I->login();
    $I->amOnMailpoetPage('Subscribers');
    $I->searchFor('wp@example.com');
    $I->waitForText('wp@example.com');
  }

  public function sendConfirmationEmail(\AcceptanceTester $I) {
    $I->wantTo('Send confirmation email');

    $disallowed_email = 'disallowed@example.com';
    $allowed_email = 'allowed@example.com';

    $subscriber_resend_disallowed = (new Subscriber())
      ->withEmail($disallowed_email)
      ->withStatus('unconfirmed')
      ->withCountConfirmations(ConfirmationEmailMailer::MAX_CONFIRMATION_EMAILS)
      ->create();

    $subscriber_resend_allowed = (new Subscriber())
      ->withEmail($allowed_email)
      ->withStatus('unconfirmed')
      ->withCountConfirmations(0)
      ->create();

    $I->login();
    $I->amOnMailpoetPage('Subscribers');

    $I->moveMouseOver(['xpath' => '//*[text()="' . $disallowed_email . '"]//ancestor::tr']);
    $I->dontSee('Resend confirmation email', '//*[text()="' . $disallowed_email . '"]//ancestor::tr');

    $I->clickItemRowActionByItemName($allowed_email, 'Resend confirmation email');
    $I->waitForText('1 confirmation email has been sent.');

    $I->amOnMailboxAppPage();
    $I->waitForText('Confirm your subscription');
  }

}

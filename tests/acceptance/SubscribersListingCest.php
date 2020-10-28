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

  public function bulkUnsubscribe(\AcceptanceTester $i) {
    $i->wantTo('Unsubscribe subscribers using a bulk action');
    $i->wantTo('Setup data');
    $subscriber1 = (new Subscriber())
      ->withEmail('subscriber1@example.com')
      ->withStatus('subscribed')
      ->create();
    $subscriber2 = (new Subscriber())
      ->withEmail('subscriber2@example.com')
      ->withStatus('subscribed')
      ->create();
    $subscriber3 = (new Subscriber())
      ->withEmail('subscriber3@example.com')
      ->withStatus('subscribed')
      ->create();
    $subscriber4 = (new Subscriber())
      ->withEmail('subscriber4@example.com')
      ->withStatus('subscribed')
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Subscribers');

    $i->wantTo('Select first two subscribers and unsubscribe them');
    $i->click("[data-automation-id='listing-row-checkbox-$subscriber1->id']");
    $i->click("[data-automation-id='listing-row-checkbox-$subscriber2->id']");

    $i->waitForElement("[data-automation-id='action-unsubscribe']");
    $i->click("[data-automation-id='action-unsubscribe']");

    $i->wantTo('Confirm the action in the modal window');
    $i->waitForElement("[data-automation-id='bulk-unsubscribe-confirm']");
    $i->click("[data-automation-id='bulk-unsubscribe-confirm']");

    $i->wantTo('Check the final status');
    $i->waitForText('subscriber2@example.com');
    $i->waitForText('Unsubscribed', 10, "[data-automation-id='listing_item_$subscriber1->id']");
    $i->waitForText('Unsubscribed', 10, "[data-automation-id='listing_item_$subscriber2->id']");
    $i->waitForText('Subscribed', 10, "[data-automation-id='listing_item_$subscriber3->id']");
    $i->waitForText('Subscribed', 10, "[data-automation-id='listing_item_$subscriber4->id']");
    $i->dontSee('Unsubscribed', "[data-automation-id='listing_item_$subscriber3->id']");
    $i->dontSee('Unsubscribed', "[data-automation-id='listing_item_$subscriber4->id']");
  }
}

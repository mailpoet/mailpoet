<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class SentNewsletterCest {
  public function disableLastStep(\AcceptanceTester $i) {
    $i->wantTo('See that last step is disabled fot sent standard email');

    $newsletter = (new Newsletter())
      ->withSentStatus()
      ->withSubject('Sent newsletter')
      ->create();

    $i->login();
    $i->amEditingNewsletter($newsletter->getId());
    $i->waitForElement('.mailpoet_save_next.button-disabled');
    $i->see('This email has already been sent.');
    $i->see('It can be edited, but not sent again.');
    $i->see('Duplicate this email if you want to send it again.');
  }
}

<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class SentNewsletterCest {

  public function disableLastStep(\AcceptanceTester $I) {
    $I->wantTo('See that last step is disabled fot sent standard email');

    $newsletter = (new Newsletter())
      ->withSentStatus()
      ->withSubject('Sent newsletter')
      ->create();

    $I->login();
    $I->amEditingNewsletter($newsletter->id);
    $I->waitForElement('.mailpoet_save_next.button-disabled');
    $I->see('This email has already been sent.');
    $I->see('It can be edited, but not sent again.');
    $I->see('Duplicate this email if you want to send it again.');
  }

}

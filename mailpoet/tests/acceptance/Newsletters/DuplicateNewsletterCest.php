<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class DuplicateNewsletterCest {
  public function duplicateNewsletter(\AcceptanceTester $i) {
    $newsletterName = 'Duplicate Newsletter';
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletterName)->create();
    $i->wantTo('Duplicate a newsletter');
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->waitForText($newsletterName);
    $i->clickItemRowActionByItemName($newsletterName, 'Duplicate');
    $i->waitForText('Copy of ' . $newsletterName);
  }
}

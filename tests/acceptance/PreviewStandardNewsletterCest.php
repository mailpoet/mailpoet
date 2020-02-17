<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class PreviewStandardNewsletterCest {
  public function previewStandardNewsletter(\AcceptanceTester $i) {
    $newsletterName = 'Preview in Browser Newsletter';
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletterName)->create();
    $i->wantTo('Preview a standard newsletter');
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->waitForText($newsletterName);
    $i->clickItemRowActionByItemName($newsletterName, 'Preview');
    $i->switchToNextTab();
    $i->waitForElement('.mailpoet_template');
  }
}

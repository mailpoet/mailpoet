<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

require_once __DIR__ . '/../DataFactories/Newsletter.php';

class PreviewStandardNewsletterCest {

  function previewStandardNewsletter(\AcceptanceTester $I) {
    $newsletterName = 'Preview in Browser Newsletter';
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletterName)->create();
    $I->wantTo('Preview a standard newsletter');
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->waitForText($newsletterName);
    $I->clickItemRowActionByItemName($newsletterName, 'Preview');
    $I->switchToNextTab();
    $I->waitForElement('.mailpoet_template');
  }
}
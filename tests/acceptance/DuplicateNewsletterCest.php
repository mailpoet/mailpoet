<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

require_once __DIR__ . '/../DataFactories/Newsletter.php';

class DuplicateNewsletterCest {
  function duplicateNewsletter(\AcceptanceTester $I) {
    $newsletter_name = 'Duplicate Newsletter';
    $newsletter = new Newsletter();
    $newsletter->withSubject($newsletter_name)->create();
    $I->wantTo('Duplicate a newsletter');
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->waitForText($newsletter_name);
    $I->clickItemRowActionByItemName($newsletter_name, 'Duplicate');
    $I->waitForText('Copy of '$newsletter_name);
  }


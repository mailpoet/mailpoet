<?php

namespace MailPoet\Test\Acceptance;

class BeamerOpenAndCloseCest {
  function openAndCloseBeamer(\AcceptanceTester $I){
    //$beamer_carrot = '[data-automation-id="beamer-icon"]';
    $I->wantTo('Open and close Beamer sidebar');
    $I->login();
    $I->amOnMailPoetPage('Emails');
    $I->click('.beamer-selector');
    $I->waitForText('What\'s new in MailPoet');
    $I->click('.headerClose');
    $I->dontSeeElement('.headerTitle');
  }
}
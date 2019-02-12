<?php

namespace MailPoet\Test\Acceptance;

class BeamerAcceptanceCest {
  function openAndCloseBeamer(\AcceptanceTester $I) {
    $I->wantTo('Open and close Beamer sidebar');
    $I->login();
    $I->amOnMailPoetPage('Emails');
    $I->click('.mailpoet_feature_announcement_icon');
    $I->waitForElement('#beamerNews');
    $I->switchToIframe('beamerNews');
    $I->click('.headerClose');
    $I->switchToIframe();
    $I->dontSeeElement('#beamerNews');
    $I->dontSeeElement('#beamerSelector');
  }
}

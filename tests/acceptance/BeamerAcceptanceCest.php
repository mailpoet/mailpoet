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
    $I->waitForElement('.headerClose');
    $I->click('.headerClose');
    $I->switchToIframe();
    //necessary to avoid race condition with animation
    $I->wait(2);
    $I->dontSeeElement('#beamerNews');
    $I->dontSeeElement('#beamerSelector');
  }
}

<?php

namespace MailPoet\Test\Acceptance;

class FreePlanAnnouncementCest {
  const NOTICE_SELECTOR = '[data-automation-id="free-plan-announcement"]';

  function showAndCloseNotice(\AcceptanceTester $I) {
    $I->wantTo('Show and close free plan announcement');
    $I->login();
    $I->amOnMailPoetPage('Emails');
    $I->waitForText('Add New');
    $I->waitForElement(self::NOTICE_SELECTOR);
    $I->click(self::NOTICE_SELECTOR . ' .notice-dismiss');
    $I->waitForElementNotVisible(self::NOTICE_SELECTOR);
    $I->amOnMailPoetPage('Emails');
    $I->waitForText('Add New');
    $I->waitForElementNotVisible(self::NOTICE_SELECTOR);
  }
}

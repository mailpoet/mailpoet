<?php

class SettingsPageCest {

  function _before(AcceptanceTester $I) {
    $I->login();
  }

  function iCanSeeTitle(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-settings');
    $I->see('Settings');
  }

  function iCanReachItFromTheWelcomePage(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet');
    $I->see('Welcome!');
    $I->click('Setup');
    $I->see('Settings');
  }

}

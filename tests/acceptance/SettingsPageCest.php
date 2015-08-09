<?php
use \AcceptanceTester;

class SettingsPageCest {

  function _before(AcceptanceTester $I) {
    $I->login();
  }

  function iCanSeeTheSettingsPage(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-settings');
    $I->see('Settings');
  }

  function iCanGoToSettingsFromWelcomePage(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet');
    $I->see('Welcome!');
    $I->click('Setup');
    $I->see('Settings');
  }

  function _after(AcceptanceTester $I) {
  }
}

<?php

class WelcomePageCest {

  function _before(AcceptanceTester $I) {
    $I->login();
  }

  function iCanSeeTheWelcomePage(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet');
    $I->see('Welcome!');
  }

  function _after(AcceptanceTester $I) {
  }
}

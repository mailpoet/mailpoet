<?php

class SubscribersPageCest {

  function _before(AcceptanceTester $I) {
    $I->login();
  }

  function iCanSeeTheTitle(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-subscribers');
    $I->see('Subscribers');
  }

  function _after(AcceptanceTester $I) {
  }
}

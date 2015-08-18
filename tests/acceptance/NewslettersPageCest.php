<?php

class NewslettersPageCest {

  function _before(AcceptanceTester $I) {
    $I->login();
  }

  function iCanSeeTheTitle(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletters');
    $I->see('Newsletters');
  }

  function _after(AcceptanceTester $I) {
  }
}

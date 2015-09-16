<?php

class HomePageCest {

  function _before(AcceptanceTester $I) {
  }

  function iCanSeeTitle(AcceptanceTester $I) {
    $I->amOnPage('/');
    $I->see('Hello');
  }

  function _after(AcceptanceTester $I) {
  }
}

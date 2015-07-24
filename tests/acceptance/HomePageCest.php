<?php
use \AcceptanceTester;

class HomePageCest {

    public function _before(AcceptanceTester $I) {
    }

    public function IcanSeeATitle(AcceptanceTester $I) {
      $I->amOnPage('/');
      $I->see('Hello');
    }

    public function _after(AcceptanceTester $I) {
    }
}

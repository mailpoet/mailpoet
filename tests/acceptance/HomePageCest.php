<?php
use \AcceptanceTester;

class HomePageCest {

    public function _before(AcceptanceTester $I) {
    }

    public function it_has_a_title(AcceptanceTester $I) {
      $I->amOnPage('/');
      $I->see('Hello');
    }

    public function _after(AcceptanceTester $I) {
    }
}

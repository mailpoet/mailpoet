<?php

class ActivationCest {

  public function _before(AcceptanceTester $I) {
    $I->login();
  }

  public function iCanActivate(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/plugins.php');

    $I->see('MailPoet');
    $I->click('#mailpoet .deactivate a');
    $I->see('Plugin deactivated');

    $I->see('MailPoet');
    $I->click('#mailpoet .activate a');
    $I->see('Plugin Activated');
  }

  public function _after(AcceptanceTester $I) {
  }
}

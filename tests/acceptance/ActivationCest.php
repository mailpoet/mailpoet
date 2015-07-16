<?php
use \AcceptanceTester;

class ActivationCest {

    public function _before(AcceptanceTester $I) {
      $I->amOnPage('/wp-admin');
      $I->fillField('Username', 'admin');
      $I->fillField('Password', 'password');
      $I->click('Log In');
      $I->amOnPage('/wp-admin/plugins.php');
    }

    public function i_can_activate(AcceptanceTester $I) {
      $I->see('MailPoet');
      $I->click('#mailpoet .activate a');
      $I->see('Plugin Activated');
    }

    public function _after(AcceptanceTester $I) {
      $I->see('MailPoet');
      $I->click('#mailpoet .deactivate a');
      $I->see('Plugin deactivated');
    }
}

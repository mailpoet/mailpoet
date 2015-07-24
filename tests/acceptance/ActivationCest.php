<?php
use \AcceptanceTester;

class ActivationCest {

    public function _before(AcceptanceTester $I) {
      $I->amOnPage('/wp-login.php');
      $I->fillField('Username', getenv('WP_TEST_USER'));
      $I->fillField('Password', getenv('WP_TEST_PASSWORD'));
      $I->click('Log In');
    }

    public function iCanActivate(AcceptanceTester $I) {
      $I->amOnPage('/wp-admin/plugins.php');

      try {
        $I->see('MailPoet');
        $I->click('#mailpoet .deactivate a');
        $I->see('Plugin deactivated');
      } catch(Exception $e) {}

      $I->see('MailPoet');
      $I->click('#mailpoet .activate a');
      $I->see('Plugin Activated');
    }

    public function _after(AcceptanceTester $I) {
    }
}

<?php

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends \Codeception\Actor {
  use _generated\AcceptanceTesterActions;

  /**
   * Define custom actions here
   */
  public function login() {
    $this->amOnPage('/wp-login.php');
    $this->fillField('Username', getenv('WP_TEST_USER'));
    $this->fillField('Password', getenv('WP_TEST_PASSWORD'));
    $this->click('Log In');
  }
}

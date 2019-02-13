<?php

use MailPoet\Test\DataFactories\Form;

require_once __DIR__ . '/../DataFactories/Form.php';

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
  use _generated\AcceptanceTesterActions {
    switchToNextTab as _switchToNextTab;
    waitForElement as _waitForElement;
    waitForElementChange as _waitForElementChange;
    waitForElementClickable as _waitForElementClickable;
    waitForElementNotVisible as _waitForElementNotVisible;
    waitForElementVisible as _waitForElementVisible;
    waitForJS as _waitForJS;
    waitForText as _waitForText;
  }

  const WP_URL = 'http://wordpress';
  const MAIL_URL = 'http://mailhog:8025';

  /**
   * Define custom actions here
   */
  public function login() {
    $I = $this;
    $I->amOnPage('/wp-login.php');
    if ($I->loadSessionSnapshot('login')) {
      return;
    }
    $I->wait(1);// this needs to be here, Username is not filled properly without this line
    $I->fillField('Username', 'admin');
    $I->fillField('Password', 'password');
    $I->click('Log In');
    $I->waitForText('MailPoet', 10);
    $I->saveSessionSnapshot('login');
  }

  /**
   * Define custom actions here
   */
  public function logOut() {
    $I = $this;
    $I->amOnPage('/wp-login.php?action=logout');
    $I->click('log out');
    $I->wait(1);
    $I->deleteSessionSnapshot('login');
  }

  /**
   * Navigate to the specified Mailpoet page in the admin.
   *
   * @param string $page The page to visit e.g. Inbox or Status
   */
  public function amOnMailpoetPage($page) {
    $I = $this;
    if ($page === 'Emails') {
      $path = 'newsletters';
    } elseif ($page === 'Lists') {
      $path = 'segments';
    } elseif ($page === 'Segments') {
      $path = 'dynamic-segments';
    } else {
      $path = strtolower($page);
    }
    $I->amOnPage("/wp-admin/admin.php?page=mailpoet-$path");
  }

  /**
   * Navigate to Mailhog page and wait for angular to load
   */
  public function amOnMailboxAppPage() {
    $I = $this;
    $I->amOnUrl(self::MAIL_URL);
    // ensure that angular is loaded by checking angular specific class
    $I->waitForElement('.messages.ng-scope');
  }

  public function clickItemRowActionByItemName($item_name, $link) {
    $I = $this;
    $I->moveMouseOver(['xpath' => '//*[text()="' . $item_name . '"]//ancestor::tr']);
    $I->click($link, ['xpath' => '//*[text()="' . $item_name . '"]//ancestor::tr']);
  }

  /**
   * Select a value from select2 input field.
   *
   * @param string $value
   * @param string $element
   */
  public function selectOptionInSelect2($value, $element = 'input.select2-search__field') {
    $I = $this;
    $I->waitForElement($element);
    $I->fillField($element, $value);
    $I->pressKey($element, \WebDriverKeys::ENTER);
  }

  /**
   * Navigate to the editor for a newsletter.
   *
   * @param int $id
   */
  public function amEditingNewsletter($id) {
    $I = $this;
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletter-editor&id=' . $id);
    $I->waitForElement('[data-automation-id="newsletter_title"]');
  }

  public function createFormAndSubscribe($form = null) {
    $I = $this;
    // create form widget
    if (!$form) {
      $form_factory = new Form();
      $form = $form_factory->withName('Confirmation Form')->create();
    }
    $I->cli('widget reset sidebar-1 --allow-root');
    $I->cli('widget add mailpoet_form sidebar-1 2 --form=' . $form->id . ' --title="Subscribe to Our Newsletter" --allow-root');

    // subscribe
    $I->amOnUrl(self::WP_URL);
    $I->fillField('[data-automation-id="form_email"]', 'subscriber@example.com');
    $I->click('[data-automation-id="subscribe-submit-button"]');
    $I->waitForText('Check your inbox or spam folder to confirm your subscription.', 30, '.mailpoet_validate_success');
    $I->seeNoJSErrors();
  }

  public function waitForListingItemsToLoad() {
    $I = $this;
    $I->waitForElementNotVisible('.mailpoet_listing_loading');
  }

  public function searchFor($query, $delay = 0, $element = '#search_input', $button = 'Search') {
    $I = $this;
    $I->waitForElement($element);
    if ($delay) {
      $I->wait($delay);
    }
    $I->fillField($element, $query);
    $I->click($button);
  }

  public function switchToNextTab($offset = 1) {
    $this->_switchToNextTab($offset);

    // workaround for frozen tabs when opened by clicking on links
    $this->wait(1);
  }

  /**
   * Override waitFor* methods to have a common default timeout
   */
  public function waitForElement($element, $timeout = 10) {
    return $this->_waitForElement($element, $this->getDefaultTimeout($timeout));
  }

  public function waitForElementChange($element, \Closure $callback, $timeout = 30) {
    return $this->_waitForElementChange($element, $callback, $this->getDefaultTimeout($timeout));
  }

  public function waitForElementClickable($element, $timeout = 10) {
    return $this->_waitForElementClickable($element, $this->getDefaultTimeout($timeout));
  }

  public function waitForElementNotVisible($element, $timeout = 10) {
    return $this->_waitForElementNotVisible($element, $this->getDefaultTimeout($timeout));
  }

  public function waitForElementVisible($element, $timeout = 10) {
    return $this->_waitForElementVisible($element, $this->getDefaultTimeout($timeout));
  }

  public function waitForJS($script, $timeout = 5) {
    return $this->_waitForJS($script, $this->getDefaultTimeout($timeout));
  }

  public function waitForText($text, $timeout = 10, $selector = null) {
    return $this->_waitForText($text, $this->getDefaultTimeout($timeout), $selector);
  }

  private function getDefaultTimeout($timeout) {
    return (int)getenv('WAIT_TIMEOUT') ?: $timeout;
  }

}

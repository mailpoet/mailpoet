<?php

use MailPoet\Models\Form as FormModel;
use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;

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
class AcceptanceTester extends \Codeception\Actor { // phpcs:ignore PSR1.Classes.ClassDeclaration
  use _generated\AcceptanceTesterActions {
    cli as _cli;
    cliToArray as _cliToArray;
    cliToString as _cliToString;
    switchToNextTab as _switchToNextTab;
    waitForElement as _waitForElement;
    waitForElementChange as _waitForElementChange;
    waitForElementClickable as _waitForElementClickable;
    waitForElementNotVisible as _waitForElementNotVisible;
    waitForElementVisible as _waitForElementVisible;
    waitForJS as _waitForJS;
    waitForText as _waitForText;
  }

  const WP_DOMAIN = 'test.local';
  const WP_URL = 'http://' . self::WP_DOMAIN;
  const MAIL_URL = 'http://mailhog:8025';
  const AUTHORIZED_SENDING_EMAIL = 'staff@mailpoet.com';
  const LISTING_LOADING_SELECTOR = '.mailpoet_listing_loading';

  /**
   * Define custom actions here
   */
  public function login() {
    $I = $this;
    $I->amOnPage('/wp-login.php');
    if ($I->loadSessionSnapshot('login')) {
      return;
    }

    // remove any other WP auth & login cookies to avoid login/logout errors
    $auth_cookies = $I->grabCookiesWithPattern('/^wordpress_[a-z0-9]{32}$/') ?: [];
    $login_cookies = $I->grabCookiesWithPattern('/^wordpress_logged_in_[a-z0-9]{32}$/') ?: [];
    foreach (array_merge($auth_cookies, $login_cookies) as $cookie) {
      $I->resetCookie($cookie->getName());
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
    $I->waitForText('You are now logged out.');
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
   * Check selected value in select2..
   *
   * @param string $value
   * @param string $element
   */
  public function seeSelectedInSelect2($value, $element = '.select2-container') {
    $I = $this;
    $I->waitForElement($element);
    $I->see($value, $element);
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
    $I->waitForElementNotVisible('.velocity-animating');
  }

  public function createFormAndSubscribe($form = null) {
    $I = $this;
    // create form widget
    if (!$form) {
      $form_factory = new Form();
      $form = $form_factory->withName('Confirmation Form')->create();
    }
    $I->cli(['widget', 'reset', 'sidebar-1']);
    $I->cli(['widget', 'add', 'mailpoet_form', 'sidebar-1', '2', "--form=$form->id", '--title=Subscribe to Our Newsletter']);

    // subscribe
    $I->amOnUrl(self::WP_URL);
    $I->fillField('[data-automation-id="form_email"]', 'subscriber@example.com');
    $I->click('[data-automation-id="subscribe-submit-button"]');
    $I->waitForText(FormModel::getDefaultSuccessMessage(), 30, '.mailpoet_validate_success');
    $I->seeNoJSErrors();
  }

  public function waitForListingItemsToLoad() {
    $I = $this;
    $I->waitForElementNotVisible('.mailpoet_listing_loading');
  }

  public function clickLabelWithInput($inputName, $inputValue) {
    $I = $this;
    $I->click("//*[name()='label'][.//*[name()='input'][@name='{$inputName}'][@value='{$inputValue}']]");
  }

  public function assertAttributeContains($selector, $attribute, $contains) {
    $I = $this;
    $attributeValue = $I->grabAttributeFrom($selector, $attribute);
    expect($attributeValue)->contains($contains);
  }

  public function assertAttributeNotContains($selector, $attribute, $notContains) {
    $I = $this;
    $attributeValue = $I->grabAttributeFrom($selector, $attribute);
    expect($attributeValue)->notContains($notContains);
  }

  public function searchFor($query, $element = '#search_input', $button = 'Search') {
    $I = $this;
    $I->waitForElement($element);
    $I->waitForElementNotVisible(self::LISTING_LOADING_SELECTOR);
    $I->fillField($element, $query);
    $I->click($button);
    $I->waitForElementNotVisible(self::LISTING_LOADING_SELECTOR);
  }

  public function createListWithSubscriber() {
    $segment_factory = new Segment();
    $segment_name = 'List ' . \MailPoet\Util\Security::generateRandomString();
    $segment = $segment_factory->withName($segment_name)->create();

    $subscriber_factory = new Subscriber();
    $subscriber_email = \MailPoet\Util\Security::generateRandomString() . '@domain.com';
    $subscriber_factory->withSegments([$segment])->withEmail($subscriber_email)->create();

    return $segment_name;
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

  public function scrollToTop() {
    return $this->scrollTo('#wpcontent');
  }

  private function getDefaultTimeout($timeout) {
    return (int)getenv('WAIT_TIMEOUT') ?: $timeout;
  }

  public function activateWooCommerce() {
    $I = $this;
    $I->cli(['plugin', 'activate', 'woocommerce']);
  }
  public function deactivateWooCommerce() {
    $I = $this;
    $I->cli(['plugin', 'deactivate', 'woocommerce']);
  }

  /**
   * Order a product and create an account within the order process
   */
  public function orderProduct(array $product, $user_email, $do_register = true, $do_subscribe = true) {
    $I = $this;
    $I->addProductToCart($product);
    $I->goToCheckout();
    $I->fillCustomerInfo($user_email);
    if ($do_register) {
      $I->optInForRegistration();
    }
    $I->selectPaymentMethod();
    if ($do_subscribe) {
      $I->optInForSubscription();
    } else {
      $I->optOutOfSubscription();
    }
    $I->placeOrder();
    if ($do_register) {
      $I->logOut();
    }
  }

  /**
   * WooCommerce ordering process methods, should be used sequentially
   */

  /**
   * Add a product to cart
   */
  public function addProductToCart(array $product) {
    $I = $this;
    $I->amOnPage('product/' . $product['slug']);
    $I->click('Add to cart');
    $I->waitForText("“{$product['name']}” has been added to your cart.");
  }

  /**
   * Go to the checkout page
   */
  public function goToCheckout() {
    $I = $this;
    $I->amOnPage('checkout');
  }

  /**
   * Fill the customer info
   */
  public function fillCustomerInfo($user_email) {
    $I = $this;
    $I->fillField('billing_first_name', 'John');
    $I->fillField('billing_last_name', 'Doe');
    $I->fillField('billing_address_1', 'Address 1');
    $I->fillField('billing_city', 'Paris');
    $I->fillField('billing_email', $user_email);
    $I->fillField('billing_postcode', '75000');
    $I->fillField('billing_phone', '123456');
  }

  /**
   * Check the option for creating an account
   */
  public function optInForRegistration() {
    $I = $this;
    $I->scrollTo(['css' => '#createaccount'], 0, -40);
    $I->click('#createaccount');
  }

  /**
   * Check the option for subscribing to the WC list
   */
  public function optInForSubscription() {
    $I = $this;
    $I->checkOption('#mailpoet_woocommerce_checkout_optin');
  }

  /**
   * Uncheck the option for subscribing to the WC list
   */
  public function optOutOfSubscription() {
    $I = $this;
    $I->uncheckOption('#mailpoet_woocommerce_checkout_optin');
  }

  /**
   * Select a payment method (cheque, cod, ppec_paypal)
   */
  public function selectPaymentMethod($method = 'cod') {
    $I = $this;
    // We need to scroll with some negative offset so that the input is not hidden above the top page fold
    $approximate_payment_method_input_height = 40;
    $I->scrollTo('#payment_method_' . $method, 0, -$approximate_payment_method_input_height);
    $I->waitForElementNotVisible('.blockOverlay', 30); // wait for payment method loading overlay to disappear
    $I->click('#payment_method_' . $method);
  }

  /**
   * Place the order
   */
  public function placeOrder() {
    $I = $this;
    $I->click('Place order');
    $I->waitForText('Your order has been received');
  }

  // Enforce WP-CLI to be called with array because:
  //  - It's recommended now (https://github.com/lucatume/wp-browser/commit/6dbf93709194c630191c0c7de527b577105be743).
  //  - It's default in Symfony\Process now.
  //  - String variant is still buggy even after a fix (https://github.com/lucatume/wp-browser/commit/b078ef37917b4f0668d064ea950e4b41f1773cb6).

  public function cli(array $userCommand) {
    return $this->_cli($userCommand);
  }

  public function cliToArray(array $userCommand) {
    return $this->_cliToArray($userCommand);
  }

  public function cliToString(array $userCommand) {
    return $this->_cliToString($userCommand);
  }
}

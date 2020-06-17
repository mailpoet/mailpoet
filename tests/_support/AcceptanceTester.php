<?php

use Facebook\WebDriver\Exception\UnrecognizedExceptionException;
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
// phpcs:ignore PSR1.Classes.ClassDeclaration
class AcceptanceTester extends \Codeception\Actor {
  use _generated\AcceptanceTesterActions {
    cli as _cli;
    cliToArray as _cliToArray;
    cliToString as _cliToString;
    click as _click;
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
    $i = $this;
    $i->amOnPage('/wp-login.php');
    if ($i->loadSessionSnapshot('login')) {
      return;
    }

    // remove any other WP auth & login cookies to avoid login/logout errors
    $authCookies = $i->grabCookiesWithPattern('/^wordpress_[a-z0-9]{32}$/') ?: [];
    $loginCookies = $i->grabCookiesWithPattern('/^wordpress_logged_in_[a-z0-9]{32}$/') ?: [];
    foreach (array_merge($authCookies, $loginCookies) as $cookie) {
      $i->resetCookie($cookie->getName());
    }

    $i->wait(1);// this needs to be here, Username is not filled properly without this line
    $i->fillField('Username', 'admin');
    $i->fillField('Password', 'password');
    $i->click('Log In');
    $i->waitForText('MailPoet', 10);
    $i->saveSessionSnapshot('login');
  }

  /**
   * Define custom actions here
   */
  public function logOut() {
    $i = $this;
    $i->amOnPage('/wp-login.php?action=logout');
    $i->click('log out');
    $i->waitForText('You are now logged out.');
    $i->deleteSessionSnapshot('login');
  }

  /**
   * Navigate to the specified Mailpoet page in the admin.
   *
   * @param string $page The page to visit e.g. Inbox or Status
   */
  public function amOnMailpoetPage($page) {
    $i = $this;
    if ($page === 'Emails') {
      $path = 'newsletters';
    } elseif ($page === 'Lists') {
      $path = 'segments';
    } else {
      $path = strtolower($page);
    }
    $i->amOnPage("/wp-admin/admin.php?page=mailpoet-$path");
  }

  /**
   * Navigate to Mailhog page and wait for angular to load
   */
  public function amOnMailboxAppPage() {
    $i = $this;
    $i->amOnUrl(self::MAIL_URL);
    // ensure that angular is loaded by checking angular specific class
    $i->waitForElement('.messages.ng-scope');
  }

  public function clickItemRowActionByItemName($itemName, $link) {
    $i = $this;
    $i->moveMouseOver(['xpath' => '//*[text()="' . $itemName . '"]//ancestor::tr']);
    $i->click($link, ['xpath' => '//*[text()="' . $itemName . '"]//ancestor::tr']);
  }

  /**
   * Select a value from select2 input field.
   *
   * @param string $value
   * @param string $element
   */
  public function selectOptionInSelect2($value, $element = 'input.select2-search__field') {
    $i = $this;
    $i->waitForElement($element);
    $i->fillField($element, $value);
    $i->pressKey($element, \WebDriverKeys::ENTER);
  }

  /**
   * Check selected value in select2..
   *
   * @param string $value
   * @param string $element
   */
  public function seeSelectedInSelect2($value, $element = '.select2-container') {
    $i = $this;
    $i->waitForElement($element);
    $i->see($value, $element);
  }

  /**
   * Navigate to the editor for a newsletter.
   *
   * @param int $id
   */
  public function amEditingNewsletter($id) {
    $i = $this;
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletter-editor&id=' . $id);
    $i->waitForElement('[data-automation-id="newsletter_title"]');
    $i->waitForElementNotVisible('.velocity-animating');
  }

  public function createFormAndSubscribe($form = null) {
    $i = $this;
    // create form widget
    if (!$form) {
      $formFactory = new Form();
      $form = $formFactory->withName('Confirmation Form')->create();
    }
    $i->cli(['widget', 'reset', 'sidebar-1']);
    $i->cli(['widget', 'add', 'mailpoet_form', 'sidebar-1', '2', "--form=$form->id", '--title=Subscribe to Our Newsletter']);

    // subscribe
    $i->amOnUrl(self::WP_URL);
    $i->fillField('[data-automation-id="form_email"]', 'subscriber@example.com');
    $i->click('[data-automation-id="subscribe-submit-button"]');
    $i->waitForText(FormModel::getDefaultSuccessMessage(), 30, '.mailpoet_validate_success');
    $i->seeNoJSErrors();
  }

  public function waitForListingItemsToLoad() {
    $i = $this;
    $i->waitForElementNotVisible('.mailpoet_listing_loading');
  }

  public function clickLabelWithInput($inputName, $inputValue) {
    $i = $this;
    $i->click("//*[name()='label'][.//*[name()='input'][@name='{$inputName}'][@value='{$inputValue}']]");
  }

  public function assertAttributeContains($selector, $attribute, $contains) {
    $i = $this;
    $attributeValue = $i->grabAttributeFrom($selector, $attribute);
    expect($attributeValue)->contains($contains);
  }

  public function assertAttributeNotContains($selector, $attribute, $notContains) {
    $i = $this;
    $attributeValue = $i->grabAttributeFrom($selector, $attribute);
    expect($attributeValue)->notContains($notContains);
  }

  public function searchFor($query, $element = '#search_input', $button = 'Search') {
    $i = $this;
    $i->waitForElement($element);
    $i->waitForElementNotVisible(self::LISTING_LOADING_SELECTOR);
    $i->fillField($element, $query);
    $i->click($button);
    $i->waitForElementNotVisible(self::LISTING_LOADING_SELECTOR);
  }

  public function createListWithSubscriber() {
    $segmentFactory = new Segment();
    $segmentName = 'List ' . \MailPoet\Util\Security::generateRandomString();
    $segment = $segmentFactory->withName($segmentName)->create();

    $subscriberFactory = new Subscriber();
    $subscriberEmail = \MailPoet\Util\Security::generateRandomString() . '@domain.com';
    $subscriberFactory->withSegments([$segment])->withEmail($subscriberEmail)->create();

    return $segmentName;
  }

  public function switchToNextTab($offset = 1) {
    $this->_switchToNextTab($offset);

    // workaround for frozen tabs when opened by clicking on links
    $this->wait(1);
  }

  public function click($link, $context = null) {
    // retry click in case of "element click intercepted... Other element would receive the click" error
    $retries = 3;
    while (true) {
      try {
        $retries--;
        $this->_click($link, $context);
        break;
      } catch (UnrecognizedExceptionException $e) {
        if ($retries > 0 && strpos($e->getMessage(), 'element click intercepted') !== false) {
          continue;
        }
        throw $e;
      }
    }
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
    $i = $this;
    $i->cli(['plugin', 'activate', 'woocommerce']);
  }

  public function deactivateWooCommerce() {
    $i = $this;
    $i->cli(['plugin', 'deactivate', 'woocommerce']);
  }

  /**
   * Order a product and create an account within the order process
   */
  public function orderProduct(array $product, $userEmail, $doRegister = true, $doSubscribe = true) {
    $i = $this;
    $i->addProductToCart($product);
    $i->goToCheckout();
    $i->fillCustomerInfo($userEmail);
    if ($doRegister) {
      $i->optInForRegistration();
    }
    $i->selectPaymentMethod();
    if ($doSubscribe) {
      $i->optInForSubscription();
    } else {
      $i->optOutOfSubscription();
    }
    $i->placeOrder();
    if ($doRegister) {
      $i->logOut();
    }
  }

  /**
   * WooCommerce ordering process methods, should be used sequentially
   */

  /**
   * Add a product to cart
   */
  public function addProductToCart(array $product) {
    $i = $this;
    $i->amOnPage('product/' . $product['slug']);
    $i->click('Add to cart');
    $i->waitForText("“{$product['name']}” has been added to your cart.");
  }

  /**
   * Go to the checkout page
   */
  public function goToCheckout() {
    $i = $this;
    $i->amOnPage('checkout');
  }

  /**
   * Fill the customer info
   */
  public function fillCustomerInfo($userEmail) {
    $i = $this;
    $i->fillField('billing_first_name', 'John');
    $i->fillField('billing_last_name', 'Doe');
    $i->fillField('billing_address_1', 'Address 1');
    $i->fillField('billing_city', 'Paris');
    $i->fillField('billing_email', $userEmail);
    $i->fillField('billing_postcode', '75000');
    $i->fillField('billing_phone', '123456');
  }

  /**
   * Check the option for creating an account
   */
  public function optInForRegistration() {
    $i = $this;
    $i->scrollTo(['css' => '#createaccount'], 0, -40);
    $i->click('#createaccount');
  }

  /**
   * Check the option for subscribing to the WC list
   */
  public function optInForSubscription() {
    $i = $this;
    $i->waitForElementClickable('#mailpoet_woocommerce_checkout_optin');
    $i->checkOption('#mailpoet_woocommerce_checkout_optin');
  }

  /**
   * Uncheck the option for subscribing to the WC list
   */
  public function optOutOfSubscription() {
    $i = $this;
    $i->waitForElementClickable('#mailpoet_woocommerce_checkout_optin');
    $i->uncheckOption('#mailpoet_woocommerce_checkout_optin');
  }

  /**
   * Select a payment method (cheque, cod, ppec_paypal)
   */
  public function selectPaymentMethod($method = 'cod') {
    $i = $this;
    // We need to scroll with some negative offset so that the input is not hidden above the top page fold
    $approximatePaymentMethodInputHeight = 40;
    $i->scrollTo('#payment_method_' . $method, 0, -$approximatePaymentMethodInputHeight);
    $i->waitForElementNotVisible('.blockOverlay', 30); // wait for payment method loading overlay to disappear
    $i->click('#payment_method_' . $method);
  }

  /**
   * Place the order
   */
  public function placeOrder() {
    $i = $this;
    $i->click('Place order');
    $i->waitForText('Your order has been received');
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

  /**
   * Creates post and returns its URL
   */
  public function createPost(string $title, string $body): string {
    $post = $this->cliToArray(['post', 'create', '--format=json', '--porcelain', '--post_status=publish', '--post_type=post', '--post_title="' . $title . '"', '--post_content="' . $body . '"']);
    $postData = $this->cliToArray(['post', 'get', $post[0], '--format=json']);
    $postData = json_decode($postData[0], true);
    return $postData['guid'];
  }

  public function addFromBlockInEditor($name) {
    $i = $this;
    $i->click('.block-list-appender button');// CLICK the button that adds new blocks
    $i->fillField('Search for a block', $name);
    $i->waitForText($name, 5, '.block-editor-block-types-list__item-title');
    $i->click($name, '.block-editor-block-types-list__list-item');
  }

  public function saveFormInEditor() {
    $i = $this;
    $i->click('[data-automation-id="form_save_button"]');
    $i->waitForText('Form saved', 10, '.automation-dismissible-notices');
    $i->seeNoJSErrors();
  }
}

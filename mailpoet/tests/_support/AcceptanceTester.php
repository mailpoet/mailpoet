<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

use Facebook\WebDriver\Exception\UnrecognizedExceptionException;
use Facebook\WebDriver\Exception\WebDriverException;
use Facebook\WebDriver\WebDriverKeys;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\FormEntity;
use MailPoet\Form\FormMessageController;
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
  const LISTING_LOADING_SELECTOR = '.mailpoet-listing-loading';
  const WOO_COMMERCE_PLUGIN = 'woocommerce';
  const WOO_COMMERCE_BLOCKS_PLUGIN = 'woo-gutenberg-products-block';
  const WOO_COMMERCE_MEMBERSHIPS_PLUGIN = 'woocommerce-memberships';
  const WOO_COMMERCE_SUBSCRIPTIONS_PLUGIN = 'woocommerce-subscriptions';

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
    for ($x = 1; $x <= 3; $x++) {
      try {
        $itemNameCellXpath = ['xpath' => '//tr//*[text()="' . $itemName . '"]//ancestor::td'];
        $linkXpath = ['xpath' => '//*[text()="' . $itemName . '"]//ancestor::td//a[text()="' . $link . '"]'];
        $i->moveMouseOver($itemNameCellXpath);
        $i->waitForElementClickable($linkXpath, 3);
        $i->click($linkXpath);
        break;
      } catch (Exception $exception) {
        $this->wait(1);
        continue;
      }
    }
  }

  /**
   * Select a value from select2 input field.
   * For multiple selection the element is textarea.select2-search__field (default),
   * for single selection specify the input.select2-search__field element.
   *
   * @param string $value
   * @param string $element
   */
  public function selectOptionInSelect2($value, $element = 'textarea.select2-search__field') {
    $i = $this;
    for ($x = 1; $x <= 3; $x++) {
      try {
        $i->waitForElement($element);
        $i->fillField($element, $value);
        $optionsContainer = $i->grabAttributeFrom($element, 'aria-controls');
        // Wait until the searched value is in select options. There might be some delay on API
        $i->waitForText($value, 5, "#$optionsContainer");
        $i->pressKey($element, WebDriverKeys::ENTER);
        $i->seeSelectedInSelect2($value);
        break;
      } catch (Exception $exception) {
        $this->wait(1);
        continue;
      }
    }
    $i->seeSelectedInSelect2($value);
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

  public function selectOptionInReactSelect($value, $selector) {
    $i = $this;
    $i->waitForElement($selector);
    $i->fillField($selector . ' input', $value);
    $i->pressKey($selector . ' input', WebDriverKeys::RETURN_KEY);
  }

  /**
   * Navigate to the editor for a newsletter.
   *
   * @param int|null $id
   */
  public function amEditingNewsletter($id) {
    $i = $this;
    if (is_null($id)) {
      throw new \Exception('No valid id passed');
    }
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-newsletter-editor&id=' . $id);
    $i->waitForElement('[data-automation-id="newsletter_title"]');
    $i->waitForElementNotVisible('.velocity-animating');
  }

  public function createFormAndSubscribe(FormEntity $form = null) {
    $i = $this;
    // create form widget
    if (!$form instanceof FormEntity) {
      $formFactory = new Form();
      $form = $formFactory->withName('Confirmation Form')->create();
    }
    $i->cli(['widget', 'reset', 'sidebar-1']);
    $i->cli(['widget', 'add', 'mailpoet_form', 'sidebar-1', '2', "--form={$form->getId()}", '--title="Subscribe to Our Newsletter"']);

    // subscribe
    /** @var FormMessageController $messageController */
    $messageController = ContainerWrapper::getInstance()->get(FormMessageController::class);

    $i->amOnUrl(self::WP_URL);
    $i->fillField('[data-automation-id="form_email"]', 'subscriber@example.com');
    $i->click('[data-automation-id="subscribe-submit-button"]');
    $i->waitForText($messageController->getDefaultSuccessMessage(), 30, '.mailpoet_validate_success');
    $i->seeNoJSErrors();
  }

  public function waitForListingItemsToLoad() {
    $i = $this;
    $i->waitForElementNotVisible('.mailpoet-listing-loading');
  }

  public function waitForEmailSendingOrSent() {
    $i = $this;
    $i->waitForElement('.mailpoet-listing-status:not(.mailpoet-listing-status-unknown)', 30);
  }

  public function clickLabelWithInput($inputName, $inputValue) {
    $i = $this;
    $i->click("//*[name()='label'][.//*[name()='input'][@name='{$inputName}'][@value='{$inputValue}']]");
  }

  public function clickToggleYes($yesNoCSSSelector) {
    $i = $this;
    $i->click($yesNoCSSSelector . ' .mailpoet-form-yesno-yes');
  }

  public function clickToggleNo($yesNoCSSSelector) {
    $i = $this;
    $i->click($yesNoCSSSelector . ' .mailpoet-form-yesno-no');
  }

  public function assertAttributeContains($selector, $attribute, $contains) {
    $i = $this;
    $attributeValue = $i->grabAttributeFrom($selector, $attribute);
    expect($attributeValue)->stringContainsString($contains);
  }

  public function assertCssProperty($cssSelector, $cssProperty, $value) {
    $i = $this;
    $attributeValue = $i->executeInSelenium(function (\Facebook\WebDriver\WebDriver $webdriver) use ($cssSelector, $cssProperty){
      return $webdriver->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector($cssSelector))->getCSSValue($cssProperty);
    });
    expect($attributeValue)->equals($value);
  }

  public function assertAttributeNotContains($selector, $attribute, $notContains) {
    $i = $this;
    $attributeValue = $i->grabAttributeFrom($selector, $attribute);
    expect($attributeValue)->stringNotContainsString($notContains);
  }

  public function searchFor($query, $element = '#search_input') {
    $i = $this;
    $i->waitForElement($element);
    $i->waitForElementNotVisible(self::LISTING_LOADING_SELECTOR);
    $i->clearField($element);
    $i->fillField($element, $query);
    $i->pressKey($element, WebDriverKeys::ENTER);
    $i->waitForElementNotVisible(self::LISTING_LOADING_SELECTOR);
  }

  public function createListWithSubscriber() {
    $segmentFactory = new Segment();
    $segmentName = 'List ' . \MailPoet\Util\Security::generateRandomString();
    $segment = $segmentFactory->withName($segmentName)->create();

    $subscriberFactory = new Subscriber();
    $subscriberEmail = \MailPoet\Util\Security::generateRandomString() . '@domain.com';
    $subscriberFirstName = 'John';
    $subscriberLastName = 'Doe';
    $subscriberFactory->withSegments([$segment])
      ->withEmail($subscriberEmail)
      ->withFirstName($subscriberFirstName)
      ->withLastName($subscriberLastName)
      ->create();

    return $segmentName;
  }

  public function switchToNextTab($offset = 1) {
    // Try switching multiple times. Sometimes we get an exception and maybe the tab is not ready.
    for ($x = 1; $x <= 3; $x++) {
      try {
        $this->_switchToNextTab($offset);
        break;
      } catch (Exception $exception) {
        $this->wait(1);
        continue;
      }
    }
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
      } catch (WebDriverException $e) {
        if ($retries > 0 && preg_match('(element click intercepted|element not interactable)', $e->getMessage()) === 1) {
          $this->wait(0.2);
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

  public function waitForNoticeAndClose($text, $timeout = 10, $selector = null) {
    $this->_waitForText($text, $this->getDefaultTimeout($timeout), $selector);
    $this->waitForElementVisible('.notice-dismiss', 1);
    $this->click('.notice-dismiss');
  }

  public function scrollToTop() {
    return $this->scrollTo('#wpcontent');
  }

  private function getDefaultTimeout($timeout) {
    return (int)getenv('WAIT_TIMEOUT') ?: $timeout;
  }

  public function activateWooCommerce() {
    $i = $this;
    $i->cli(['plugin', 'activate', self::WOO_COMMERCE_PLUGIN]);
  }

  public function deactivateWooCommerce() {
    $i = $this;
    $i->cli(['plugin', 'deactivate', self::WOO_COMMERCE_PLUGIN]);
  }

  public function activateWooCommerceBlocks() {
    $i = $this;
    $i->cli(['plugin', 'activate', self::WOO_COMMERCE_BLOCKS_PLUGIN]);
  }

  public function deactivateWooCommerceBlocks() {
    $i = $this;
    $i->cli(['plugin', 'deactivate', self::WOO_COMMERCE_BLOCKS_PLUGIN]);
  }

  public function activateWooCommerceMemberships() {
    $i = $this;
    $i->cli(['plugin', 'activate', self::WOO_COMMERCE_MEMBERSHIPS_PLUGIN]);
  }

  public function deactivateWooCommerceMemberships() {
    $i = $this;
    $i->cli(['plugin', 'deactivate', self::WOO_COMMERCE_MEMBERSHIPS_PLUGIN]);
  }

  public function activateWooCommerceSubscriptions() {
    $i = $this;
    $i->cli(['plugin', 'activate', self::WOO_COMMERCE_SUBSCRIPTIONS_PLUGIN]);
  }

  public function deactivateWooCommerceSubscriptions() {
    $i = $this;
    $i->cli(['plugin', 'deactivate', self::WOO_COMMERCE_SUBSCRIPTIONS_PLUGIN]);
  }

  public function checkPluginIsActive(string $plugin): bool {
    $i = $this;
    return in_array($plugin, $i->grabOptionFromDatabase('active_plugins', true));
  }

  public function getWooCommerceVersion(): string {
    $i = $this;
    return $i->cliToString(['plugin', 'get', self::WOO_COMMERCE_PLUGIN, '--field=version']);
  }

  public function getWooCommerceBlocksVersion(): string {
    $i = $this;
    return $i->cliToString(['plugin', 'get', self::WOO_COMMERCE_BLOCKS_PLUGIN, '--field=version']);
  }

  public function getWordPressVersion(): string {
    $i = $this;
    return $i->cliToString(['core', 'version']);
  }

  public function orderProductWithoutRegistration(array $product, $userEmail, $doSubscribe = true) {
    $this->orderProduct($product, $userEmail, false, $doSubscribe);
  }

  public function orderProductWithRegistration(array $product, $userEmail, $doSubscribe = true) {
    $this->orderProduct($product, $userEmail, true, $doSubscribe);
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
    // ensure action scheduler jobs are done
    $i->triggerMailPoetActionScheduler();
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
    $isCheckboxVisible = $i->executeJS('return document.getElementById("createaccount")');
    if ($isCheckboxVisible) {
      $i->checkOption('#createaccount');
    }
  }

  /**
   * Check the option for subscribing to the WC list
   */
  public function optInForSubscription() {
    $i = $this;
    $isCheckboxVisible = $i->executeJS('return document.getElementById("mailpoet_woocommerce_checkout_optin")');
    if ($isCheckboxVisible) {
      $i->checkOption('#mailpoet_woocommerce_checkout_optin');
    }
  }

  /**
   * Uncheck the option for subscribing to the WC list
   */
  public function optOutOfSubscription() {
    $i = $this;
    $isCheckboxVisible = $i->executeJS('return document.getElementById("mailpoet_woocommerce_checkout_optin")');
    if ($isCheckboxVisible) {
      $i->uncheckOption('#mailpoet_woocommerce_checkout_optin');
    }
  }

  /**
   * Select a payment method (cheque, cod, ppec_paypal)
   */
  public function selectPaymentMethod($method = 'cod') {
    $i = $this;
    // We need to scroll with some negative offset so that the input is not hidden above the top page fold
    $approximatePaymentMethodInputHeight = 40;
    $i->waitForElementNotVisible('.blockOverlay', 30); // wait for payment method loading overlay to disappear
    $i->scrollTo('#payment_method_' . $method, 0, -$approximatePaymentMethodInputHeight);
    $i->click('label[for="payment_method_' . $method . '"]');
    $i->wait(0.5); // Wait for animation after selecting the method.
  }

  /**
   * Place the order
   */
  public function placeOrder() {
    $i = $this;
    $i->waitForText('Place order');
    $i->click('Place order');
    $i->waitForText('Your order has been received');
  }

  /**
   * Register a customer on my-account page
   * @param string $email
   * @param bool $optIn  Whether to check optin checkbox or not
   * @throws UnrecognizedExceptionException
   */
  public function registerCustomerOnMyAccountPage(string $email, $optIn = false) {
    $i = $this;
    $i->amOnPage('my-account');
    $i->fillField('Email address', $email);
    if ($optIn) {
      $i->checkOption('mailpoet[subscribe_on_register]');
    }
    $i->click('Register', '.woocommerce-form-register');
    $i->waitForText('From your account dashboard you can view your recent orders');
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
    $post = $this->cliToString(['post', 'create', '--format=json', '--porcelain', '--post_status=publish', '--post_type=post', '--post_title="' . $title . '"', '--post_content="' . $body . '"']);
    $postData = $this->cliToString(['post', 'get', $post, '--format=json']);
    $postData = json_decode($postData, true);
    return $postData['guid'];
  }

  public function addFromBlockInEditor($name, $context = null) {
    $i = $this;
    $appender = '[data-automation-id="form_inserter_open"]';
    if ($context) {
      $appender = "$context $appender";
    }
    $i->click($appender);// CLICK the button that adds new blocks
    $i->fillField('.block-editor-inserter__search .components-search-control__input', $name);
    $i->waitForText($name, 5, '.block-editor-block-types-list__item-title');
    $i->click($name, '.block-editor-block-types-list__list-item');
    $i->click($appender);// close the inserter
  }

  public function saveFormInEditor() {
    $i = $this;
    $i->click('[data-automation-id="form_save_button"]');
    $i->waitForText('Form saved', 10, '.automation-dismissible-notices');
    $i->seeNoJSErrors();
  }

  /**
   * Checks that email was received by looking for a subject in inbox.
   * In case it was not found reloads the inbox and check once more.
   * Emails are sent via cron and might not be sent immediately.
   * @param string $subject
   */
  public function checkEmailWasReceived($subject) {
    $i = $this;
    $i->amOnMailboxAppPage();
    try {
      $i->waitForText($subject, 30);
    } catch (\Exception $e) {
      $i->amOnMailboxAppPage();
      $i->waitForText($subject, 60);
    }
  }

  /**
   * Checks if the subscriber has correct global status
   * and if some lists are passed also validates that they are subscribed in those lists
   * @param string $email
   * @param string $status
   * @param string[]|null $listsSubscribed Array of lists in that subscriber should be subscribed
   * @param string[]|null $listsNotSubscribed Array of lists in that subscriber shouldn't be subscribed
   */
  public function checkSubscriberStatusAndLists(string $email, string $status, $listsSubscribed = null, $listsNotSubscribed = null) {
    $i = $this;
    $i->amOnMailpoetPage('Subscribers');
    $i->searchFor($email);
    $i->waitForListingItemsToLoad();
    $i->waitForText($email);
    $i->see(ucfirst($status), 'td[data-colname="Status"]');
    if (is_array($listsSubscribed)) {
      foreach ($listsSubscribed as $list) {
        $i->see($list, 'td[data-colname="Lists"]');
      }
    }
    if (is_array($listsNotSubscribed)) {
      foreach ($listsNotSubscribed as $list) {
        $i->dontSee($list, 'td[data-colname="Lists"]');
      }
    }
  }

  /**
   * Checks if any confirmation email is in mailbox
   */
  public function seeConfirmationEmailWasReceived() {
    $this->checkEmailWasReceived('Confirm your subscription to');
  }

  /**
   * Checks if there are no confirmation emails in mailbox
   */
  public function seeConfirmationEmailWasNotReceived() {
    $i = $this;
    $i->amOnMailboxAppPage();
    $i->dontSee('Confirm your subscription to');
  }

  /**
   * Makes sure that there is a newsletter template of given order on given template tab
   * @return string Template element selector
   */
  public function checkTemplateIsPresent(int $templateIndex, string $templateCategory = 'standard'): string {
    $templateTab = "[data-automation-id=\"templates-$templateCategory\"]";
    $i = $this;
    $i->waitForElement($templateTab);
    $i->click($templateTab);
    $template = "[data-automation-id=\"select_template_$templateIndex\"]";
    $i->waitForElement($template);
    return $template;
  }

  public function clearFormField(string $selector) {
    $i = $this;
    $i->click($selector); // Focus in the field
    $value = $i->grabAttributeFrom($selector, 'value');

    for ($j = 0; $j < mb_strlen($value); $j++) {
      $i->pressKey($selector, WebDriverKeys::BACKSPACE);// delete the field
    }
  }

  public function canTestWithPlugin(string $pluginSlug): bool {
    $i = $this;
    try {
      $result = $i->cli(['plugin', 'is-installed', $pluginSlug]);
    } catch (\Exception $e) {
      return false;
    }
    return (int)$result === 0;
  }

  /**
   * Some tests rely on background job processing.
   * The processing runs in 1 minute interval (default Action Scheduler interval)
   * This method triggers the processing immediately so that tests don't have to wait.
   */
  public function triggerMailPoetActionScheduler(): void {
    $i = $this;
    // Reschedule MailPoet's daemon trigger action to run immediately
    $i->importSql([
      "UPDATE mp_actionscheduler_actions SET scheduled_date_gmt = SUBTIME(now(), '01:00:00'), scheduled_date_local = SUBTIME(now(), '01:00:00') WHERE hook = 'mailpoet/cron/daemon-trigger' AND status = 'pending';",
    ]);
    $i->cli(['action-scheduler', 'run', '--force']);
  }

  public function triggerAutomationActionScheduler(): void {
    $i = $this;
    // Reschedule automation trigger action to run immediately
    $i->importSql([
      "UPDATE mp_actionscheduler_actions SET scheduled_date_gmt = SUBTIME(now(), '01:00:00'), scheduled_date_local = SUBTIME(now(), '01:00:00') WHERE hook = 'mailpoet/automation/step' AND status = 'pending';",
    ]);
    $i->cli(['action-scheduler', 'run', '--force']);
  }

  public function isWooCustomOrdersTableEnabled(): bool {
    return (bool)getenv('ENABLE_COT');
  }

  public function changeGroupInListingFilter(string $name): void {
    $i = $this;
    for ($x = 1; $x <= 3; $x++) {
      try {
        $i->waitForElementClickable('[data-automation-id="filters_' . $name . '"]');
        $i->click('[data-automation-id="filters_' . $name . '"]');
        $i->seeInCurrentURL(urlencode('group[' . $name . ']'));
        break;
      } catch (Exception $exception) {
        $this->wait(0.5);
        continue;
      }
    }
    $i->seeInCurrentURL(urlencode('group[' . $name . ']'));
  }
}

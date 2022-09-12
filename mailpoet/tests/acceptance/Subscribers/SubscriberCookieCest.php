<?php

namespace MailPoet\Test\Acceptance;

use AcceptanceTester;
use Codeception\Util\Locator;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoet\Test\DataFactories\User;
use MailPoet\Test\DataFactories\WooCommerceProduct;
use PHPUnit\Framework\Assert;

class SubscriberCookieCest {
  private const SUBSCRIBER_COOKIE_NAME = 'mailpoet_subscriber';

  public function setSubscriberCookieOnSignup(AcceptanceTester $i) {
    $i->wantTo('Set subscriber cookie on signup');

    (new Settings())
      ->withSubscribeOnRegisterEnabled()
      ->withTransactionEmailsViaMailPoet();

    $email = 'test-user@example.com';

    // signup
    $i->cantSeeCookie(self::SUBSCRIBER_COOKIE_NAME);
    $i->amOnPage('/wp-login.php?action=register');
    $i->waitForElement(['css' => '.registration-form-mailpoet']);
    if (!getenv('MULTISITE')) {
      $i->fillField(['name' => 'user_login'], 'testuser');
      $i->fillField(['name' => 'user_email'], $email);
      $i->checkOption('#mailpoet_subscribe_on_register');
      $i->click('#wp-submit');
      $i->waitForText('Registration complete. Please check your email');
    } else {
      $i->fillField(['name' => 'user_name'], 'mutestuser');
      $i->fillField(['name' => 'user_email'], $email);
      $i->scrollTo(['css' => '#mailpoet_subscribe_on_register']);
      $i->checkOption('#mailpoet_subscribe_on_register');
      $i->click('Next');
      $i->waitForText('mutestuser is your new username');

      /**
       * The tracking cookie will be set once the registrant has activated and logged into
       * the wp_user account
       **/
      $i->amOnMailboxAppPage();
      $i->waitForElement('.subject.unread', 10);
      $i->click(Locator::contains('span.subject.unread', 'Activate'));
      $i->waitForText('To activate your user');
      $i->click(Locator::contains('a', 'wp-activate.php'));
      $i->switchToNextTab();
      $i->waitForText('Your account is now active');
      $password = str_replace([' ', 'Password:'], '', strval($i->grabTextFrom("//div[@id='signup-welcome'] /p[2]")));
      $i->click('Log in');
      $i->wait(1);// this needs to be here, Username is not filled properly without this line
      $i->fillField('Username', 'mutestuser');
      $i->fillField('Password', $password);
      $i->click('Log In');
      $i->waitForText('Dashboard');

    }
    // subscriber cookie should be set right after signup
    $this->checkSubscriberCookie($i, $email);
  }

  public function setSubscriberCookieOnLogin(AcceptanceTester $i) {
    $i->wantTo('Set subscriber cookie on login');

    $email = 'test-user@example.com';
    (new User())->createUser('test-user', 'subscriber', $email);

    // login
    $i->cantSeeCookie(self::SUBSCRIBER_COOKIE_NAME);
    $i->amOnPage('/wp-login.php');
    $i->wait(1); // username is not filled properly without this line
    $i->fillField('Username', 'test-user');
    $i->fillField('Password', 'test-user-password');
    $i->click('Log In');
    $i->waitForText('Dashboard');

    // subscriber cookie should be set right after login
    $this->checkSubscriberCookie($i, $email);
  }

  /**
   * @group woo
   */
  public function setSubscriberCookieOnWooCheckoutAndSubscriptionConfirmation(AcceptanceTester $i) {
    $i->wantTo('Set subscriber cookie on WooCommerce checkout and subscription confirmation');

    $i->activateWooCommerce();
    $product = (new WooCommerceProduct($i))->create();

    // order checkout
    $i->cantSeeCookie(self::SUBSCRIBER_COOKIE_NAME);
    $email = 'test-user@example.com';
    $i->orderProductWithoutRegistration($product, $email);

    // subscriber cookie should be set after order checkout
    $this->checkSubscriberCookie($i, $email);

    // click on subscription confirmation link
    $i->resetCookie(self::SUBSCRIBER_COOKIE_NAME);
    $i->cantSeeCookie(self::SUBSCRIBER_COOKIE_NAME);
    $i->checkEmailWasReceived('Confirm your subscription to MP Dev');
    $i->click(Locator::contains('span.subject', 'Confirm your subscription to MP Dev'));
    $i->switchToIframe('#preview-html');
    $i->click('I confirm my subscription!');
    $i->switchToNextTab();

    // subscriber cookie should be set after subscription confirmation
    $this->checkSubscriberCookie($i, $email);
  }

  public function setSubscriberCookieOnLinkClick(AcceptanceTester $i) {
    (new Settings())->withCronTriggerMethod('Action Scheduler');

    $subject = 'Testing newsletter';
    $newsletter = (new Newsletter())
      ->withSubject($subject)
      ->loadBodyFrom('newsletterWithText.json')
      ->create();

    $list = (new Segment())->withName('Test list')->create();

    $email = 'test-user@example.com';
    (new Subscriber())
      ->withEmail($email)
      ->withSegments([$list])
      ->create();

    // send newsletter
    $i->login();
    $i->amEditingNewsletter($newsletter->getId());
    $i->click('Next');
    $i->waitForElement('[data-automation-id="newsletter_send_form"]');
    $i->selectOptionInSelect2('Test list');
    $i->click('Send');
    $i->waitForEmailSendingOrSent();
    $i->triggerMailPoetActionScheduler();

    // click on a preview link
    $i->resetCookie(self::SUBSCRIBER_COOKIE_NAME);
    $i->cantSeeCookie(self::SUBSCRIBER_COOKIE_NAME);
    $i->checkEmailWasReceived($subject);
    $i->click(Locator::contains('span.subject', $subject));
    $i->switchToIframe('#preview-html');
    $i->click('View this in your browser');
    $i->switchToNextTab();

    // subscriber cookie should be set after link click
    $this->checkSubscriberCookie($i, $email);
  }

  private function checkSubscriberCookie(AcceptanceTester $i, string $email): void {
    $subscriberId = (int)$i->grabFromDatabase(MP_SUBSCRIBERS_TABLE, 'id', ['email' => $email]);
    $i->canSeeCookie(self::SUBSCRIBER_COOKIE_NAME);
    $cookie = $i->grabCookie(self::SUBSCRIBER_COOKIE_NAME);
    Assert::assertEquals($cookie, urlencode(json_encode(['subscriber_id' => $subscriberId]) ?: ''));
  }
}

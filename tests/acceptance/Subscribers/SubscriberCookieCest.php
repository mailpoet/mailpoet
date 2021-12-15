<?php

namespace MailPoet\Test\Acceptance;

use AcceptanceTester;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\User;
use PHPUnit\Framework\Assert;

class SubscriberCookieCest {
  private const SUBSCRIBER_COOKIE_NAME = 'mailpoet_subscriber';

  public function setSubscriberCookieOnSignup(AcceptanceTester $i) {
    $i->wantTo('Set subscriber cookie on signup');

    (new Settings())->withSubscribeOnRegisterEnabled();
    $email = 'test-user@example.com';

    // signup
    $i->cantSeeCookie(self::SUBSCRIBER_COOKIE_NAME);
    $i->amOnPage('/wp-login.php?action=register');
    $i->waitForElement(['css' => '.registration-form-mailpoet']);
    if (!getenv('MULTISITE')) {
      $i->fillField(['name' => 'user_login'], 'test-user');
      $i->fillField(['name' => 'user_email'], $email);
      $i->checkOption('#mailpoet_subscribe_on_register');
      $i->click('#wp-submit');
      $i->waitForText('Registration complete. Please check your email');
    } else {
      $i->fillField(['name' => 'user_name'], 'mu-test-user');
      $i->fillField(['name' => 'user_email'], $email);
      $i->scrollTo(['css' => '#mailpoet_subscribe_on_register']);
      $i->checkOption('#mailpoet_subscribe_on_register');
      $i->click('Next');
      $i->waitForText('mu-test-user is your new username');
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

  private function checkSubscriberCookie(AcceptanceTester $i, string $email): void {
    $subscriberId = (int)$i->grabFromDatabase(MP_SUBSCRIBERS_TABLE, 'id', ['email' => $email]);
    $i->canSeeCookie(self::SUBSCRIBER_COOKIE_NAME);
    $cookie = $i->grabCookie(self::SUBSCRIBER_COOKIE_NAME);
    Assert::assertEquals($cookie, urlencode(json_encode(['subscriber_id' => $subscriberId]) ?: ''));
  }
}

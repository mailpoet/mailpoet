<?php declare(strict_types = 1);

namespace Mailpoet\Test\Captcha\Validator;

use MailPoet\Captcha\CaptchaSession;
use MailPoet\Captcha\Validator\CaptchaValidator;
use MailPoet\Captcha\Validator\ValidationError;
use MailPoet\Config\Populator;
use MailPoet\Entities\SubscriberIPEntity;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class CaptchaValidatorTest extends \MailPoetTest {
  private CaptchaValidator $testee;
  private CaptchaSession $session;

  public function _before() {
    $this->testee = $this->diContainer->get(CaptchaValidator::class);
    $this->session = $this->diContainer->get(CaptchaSession::class);
  }

  public function testMissingCaptchaSessionIdThrowsError() {
    try {
      $this->testee->validate([]);
    } catch (ValidationError $error) {
      $meta = $error->getMeta();
      $this->assertEquals('CAPTCHA verification failed.', $meta['error']);
    }
  }

  public function testEmptyCaptchaThrowsError() {
    $this->diContainer->get(Populator::class)->up();

    try {
      $this->testee->validate(['captcha_session_id' => '123']);
    } catch (ValidationError $error) {
      $meta = $error->getMeta();
      $this->assertEquals('Please fill in the CAPTCHA.', $meta['error']);
      $this->assertTrue(array_key_exists('redirect_url', $meta));
    }
  }

  public function testWrongCaptchaThrowsError() {
    $sessionId = '123';
    $this->session->setCaptchaHash($sessionId, ['phrase' => 'abc']);
    try {
      $this->testee->validate(['captcha' => 'xyz', 'captcha_session_id' => $sessionId]);
    } catch (ValidationError $error) {
      $meta = $error->getMeta();
      $this->assertEquals('The characters entered do not match with the previous CAPTCHA.', $meta['error']);
    }
  }

  public function testThrowsErrorWhenCaptchaHasTimedOut() {
    $this->diContainer->get(Populator::class)->up();

    $sessionId = '123';
    $this->session->setCaptchaHash($sessionId, ['phrase' => null]);
    try {
      $this->testee->validate(['captcha' => 'xyz', 'captcha_session_id' => $sessionId]);
    } catch (ValidationError $error) {
      $meta = $error->getMeta();
      $this->assertEquals('Please regenerate the CAPTCHA.', $meta['error']);
      $this->assertTrue(array_key_exists('redirect_url', $meta));
    }
  }

  public function testReturnsTrueWhenCaptchaIsSolved() {
    $sessionId = '123';
    $this->session->setCaptchaHash($sessionId, ['phrase' => 'abc']);
    $this->assertTrue($this->testee->validate(['captcha' => 'abc', 'captcha_session_id' => $sessionId]));
  }

  public function testItRequiresCaptchaForFirstSubscription() {
    $email = 'non-existent-subscriber@example.com';
    $result = $this->testee->isRequired($email);
    verify($result)->equals(true);
  }

  public function testItRequiresCaptchaForUnrepeatedIPAddress() {
    $result = $this->testee->isRequired();
    verify($result)->equals(true);
  }

  public function testItTakesFilterIntoAccountToDisableCaptcha() {
    $wp = new WPFunctions;
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $filter = function () {
      return 1;
    };
    $wp->addFilter('mailpoet_subscription_captcha_recipient_limit', $filter);
    $email = 'non-existent-subscriber@example.com';
    $result = $this->testee->isRequired($email);
    verify($result)->equals(false);

    $result = $this->testee->isRequired();
    verify($result)->equals(false);

    $subscriberFactory = new SubscriberFactory();
    $subscriber = $subscriberFactory
      ->withCountConfirmations(1)
      ->create();

    $result = $this->testee->isRequired($subscriber->getEmail());
    verify($result)->equals(true);

    $ip = new SubscriberIPEntity('127.0.0.1');
    $ip->setCreatedAt(Carbon::now()->subMinutes(1));
    $this->entityManager->persist($ip);
    $this->entityManager->flush();
    $email = 'non-existent-subscriber@example.com';
    $result = $this->testee->isRequired($email);
    verify($result)->equals(true);

    unset($_SERVER['REMOTE_ADDR']);
    $wp->removeFilter('mailpoet_subscription_captcha_recipient_limit', $filter);
  }
}

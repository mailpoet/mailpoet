<?php

namespace Mailpoet\Test\Subscription\Captcha\Validator;

use Codeception\Util\Fixtures;
use MailPoet\Entities\SubscriberIPEntity;
use MailPoet\Models\Subscriber;
use MailPoet\Subscription\Captcha\CaptchaSession;
use MailPoet\Subscription\Captcha\Validator\BuiltInCaptchaValidator;
use MailPoet\Subscription\Captcha\Validator\ValidationError;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;


class BuiltInCaptchaValidatorTest extends \MailPoetTest
{

  /** @var BuiltInCaptchaValidator */
  private $testee;

  /** @var CaptchaSession */
  private $session;
  public function _before() {
    $this->testee = $this->diContainer->get(BuiltInCaptchaValidator::class);
    $this->session = $this->diContainer->get(CaptchaSession::class);
  }

  public function testEmptyCaptchaThrowsError() {

    try {
      $this->testee->validate([]);
    } catch (ValidationError $error) {
      $meta = $error->getMeta();
      $this->assertEquals('Please fill in the CAPTCHA.', $meta['error']);
      $this->assertTrue(array_key_exists('redirect_url', $meta));
    }
  }

  public function testWrongCaptchaThrowsError() {

    $this->session->init();
    $this->session->setCaptchaHash(['phrase' => 'abc']);
    try {
      $this->testee->validate(['captcha' => '123']);
    } catch (ValidationError $error) {
      $meta = $error->getMeta();
      $this->assertEquals('The characters entered do not match with the previous CAPTCHA.', $meta['error']);
    }
  }

  public function testThrowsErrorWhenCaptchaHasTimedOut() {

    $this->session->init();
    $this->session->setCaptchaHash(['phrase' => null]);
    try {
      $this->testee->validate(['captcha' => '123']);
    } catch (ValidationError $error) {
      $meta = $error->getMeta();
      $this->assertEquals('Please regenerate the CAPTCHA.', $meta['error']);
      $this->assertTrue(array_key_exists('redirect_url', $meta));
    }
  }
  public function testReturnsTrueWhenCaptchaIsSolved() {

    $this->session->init();
    $this->session->setCaptchaHash(['phrase' => 'abc']);
    $this->assertTrue($this->testee->validate(['captcha' => 'abc']));
  }



  public function testItRequiresCaptchaForFirstSubscription() {
    $email = 'non-existent-subscriber@example.com';
    $result = $this->testee->isRequired($email);
    expect($result)->equals(true);
  }

  public function testItRequiresCaptchaForUnrepeatedIPAddress() {
    $result = $this->testee->isRequired();
    expect($result)->equals(true);
  }

  public function testItTakesFilterIntoAccountToDisableCaptcha() {
    $wp = new WPFunctions;
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $filter = function() {
      return 1;
    };
    $wp->addFilter('mailpoet_subscription_captcha_recipient_limit', $filter);
    $email = 'non-existent-subscriber@example.com';
    $result = $this->testee->isRequired($email);
    expect($result)->equals(false);

    $result = $this->testee->isRequired();
    expect($result)->equals(false);

    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->countConfirmations = 1;
    $subscriber->save();
    $result = $this->testee->isRequired($subscriber->email);
    expect($result)->equals(true);

    $ip = new SubscriberIPEntity('127.0.0.1');
    $ip->setCreatedAt(Carbon::now()->subMinutes(1));
    $this->entityManager->persist($ip);
    $this->entityManager->flush();
    $email = 'non-existent-subscriber@example.com';
    $result = $this->testee->isRequired($email);
    expect($result)->equals(true);

    unset($_SERVER['REMOTE_ADDR']);
    $wp->removeFilter('mailpoet_subscription_captcha_recipient_limit', $filter);
  }
}

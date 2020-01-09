<?php

namespace MailPoet\Test\Subscription;

use Codeception\Util\Fixtures;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberIP;
use MailPoet\Subscription\Captcha;
use MailPoet\Subscription\CaptchaSession;
use MailPoet\Util\Cookies;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Functions;
use MailPoetVendor\Carbon\Carbon;

class CaptchaTest extends \MailPoetTest {
  const CAPTCHA_SESSION_ID = 'ABC';

  /** @var Captcha */
  private $captcha;

  /** @var CaptchaSession */
  private $captchaSession;

  public function _before() {
    $cookiesMock = $this->createMock(Cookies::class);
    $cookiesMock->method('get')->willReturn('abcd');
    $this->captchaSession = new CaptchaSession(new Functions());
    $this->captchaSession->init(self::CAPTCHA_SESSION_ID);
    $this->captcha = new Captcha(new WPFunctions, $this->captchaSession);
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $this->captchaSession->reset();
  }

  public function testItDoesNotRequireCaptchaForTheFirstSubscription() {
    $email = 'non-existent-subscriber@example.com';
    $result = $this->captcha->isRequired($email);
    expect($result)->equals(false);
  }

  public function testItRequiresCaptchaForRepeatedRecipient() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->countConfirmations = 1;
    $subscriber->save();
    $result = $this->captcha->isRequired($subscriber->email);
    expect($result)->equals(true);
  }

  public function testItRequiresCaptchaForRepeatedIPAddress() {
    $ip = SubscriberIP::create();
    $ip->ip = '127.0.0.1';
    $ip->createdAt = Carbon::now()->subMinutes(1);
    $ip->save();
    $email = 'non-existent-subscriber@example.com';
    $result = $this->captcha->isRequired($email);
    expect($result)->equals(true);
  }

  public function testItRendersImageAndStoresHashToSession() {
    expect($this->captchaSession->getCaptchaHash())->false();
    $image = $this->captcha->renderImage(null, null, self::CAPTCHA_SESSION_ID, true);
    expect($image)->notEmpty();
    expect($this->captchaSession->getCaptchaHash())->notEmpty();
  }

  public function _after() {
    SubscriberIP::deleteMany();
  }
}

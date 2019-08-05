<?php
namespace MailPoet\Test\Subscription;

use Carbon\Carbon;
use Codeception\Util\Fixtures;
use MailPoet\Config\Session;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberIP;
use MailPoet\Subscription\Captcha;
use MailPoet\Subscription\CaptchaSession;
use MailPoet\Util\Cookies;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Functions;

class CaptchaTest extends \MailPoetTest {

  /** @var Captcha */
  private $captcha;

  /** @var CaptchaSession */
  private $captcha_session;

  function _before() {
    $cookies_mock = $this->createMock(Cookies::class);
    $cookies_mock->method('get')->willReturn('abcd');
    $this->captcha_session = new CaptchaSession(new Functions(), new Session($cookies_mock));
    $this->captcha = new Captcha(new WPFunctions, $this->captcha_session);
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $this->captcha_session->reset();
  }

  function testItDoesNotRequireCaptchaForTheFirstSubscription() {
    $email = 'non-existent-subscriber@example.com';
    $result = $this->captcha->isRequired($email);
    expect($result)->equals(false);
  }

  function testItRequiresCaptchaForRepeatedRecipient() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->count_confirmations = 1;
    $subscriber->save();
    $result = $this->captcha->isRequired($subscriber->email);
    expect($result)->equals(true);
  }

  function testItRequiresCaptchaForRepeatedIPAddress() {
    $ip = SubscriberIP::create();
    $ip->ip = '127.0.0.1';
    $ip->created_at = Carbon::now()->subMinutes(1);
    $ip->save();
    $email = 'non-existent-subscriber@example.com';
    $result = $this->captcha->isRequired($email);
    expect($result)->equals(true);
  }

  function testItRendersImageAndStoresHashToSession() {
    expect($this->captcha_session->getCaptchaHash())->false();
    $image = $this->captcha->renderImage(null, null, true);
    expect($image)->notEmpty();
    expect($this->captcha_session->getCaptchaHash())->notEmpty();
  }

  function _after() {
    SubscriberIP::deleteMany();
  }
}

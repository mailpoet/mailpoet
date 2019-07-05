<?php
namespace MailPoet\Test\Subscription;

use Carbon\Carbon;
use Codeception\Util\Fixtures;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberIP;
use MailPoet\Subscription\Captcha;
use MailPoet\WP\Functions as WPFunctions;

class CaptchaTest extends \MailPoetTest {
  function _before() {
    $this->captcha = new Captcha(new WPFunctions);
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
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

  function testItRendersImage() {
    if (!$this->captcha->isSupported()) return;
    expect_that(empty($_SESSION[Captcha::SESSION_KEY]));
    $image = $this->captcha->renderImage(null, null, true);
    expect($image)->notEmpty();
    expect_that(!empty($_SESSION[Captcha::SESSION_KEY]));
  }

  function _after() {
    SubscriberIP::deleteMany();
  }
}

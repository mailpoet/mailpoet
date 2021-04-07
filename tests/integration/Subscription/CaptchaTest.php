<?php

namespace MailPoet\Test\Subscription;

use Codeception\Util\Fixtures;
use MailPoet\Entities\SubscriberIPEntity;
use MailPoet\Models\Subscriber;
use MailPoet\Subscribers\SubscriberIPsRepository;
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
    $subscriberIPsRepository = $this->diContainer->get(SubscriberIPsRepository::class);
    $this->captchaSession = new CaptchaSession(new Functions());
    $this->captchaSession->init(self::CAPTCHA_SESSION_ID);
    $this->captcha = new Captcha($subscriberIPsRepository, new WPFunctions, $this->captchaSession);
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
    $ip = new SubscriberIPEntity('127.0.0.1');
    $ip->setCreatedAt(Carbon::now()->subMinutes(1));
    $this->entityManager->persist($ip);
    $this->entityManager->flush();
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
    $this->truncateEntity(SubscriberIPEntity::class);
  }
}

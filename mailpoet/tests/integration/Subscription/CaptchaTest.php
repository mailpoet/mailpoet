<?php

namespace MailPoet\Test\Subscription;

use Codeception\Util\Fixtures;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberIPEntity;
use MailPoet\Models\Subscriber;
use MailPoet\Subscribers\SubscriberIPsRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscription\Captcha;
use MailPoet\Subscription\CaptchaSession;
use MailPoet\Util\Cookies;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
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
    $subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->captchaSession = new CaptchaSession(new Functions());
    $this->captchaSession->init(self::CAPTCHA_SESSION_ID);
    $this->captcha = new Captcha($subscriberIPsRepository, $subscribersRepository, new WPFunctions, $this->captchaSession);
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $this->captchaSession->reset();
  }

  public function testItRequiresCaptchaForFirstSubscription() {
    $email = 'non-existent-subscriber@example.com';
    $result = $this->captcha->isRequired($email);
    expect($result)->equals(true);
  }

  public function testItRequiresCaptchaForUnrepeatedIPAddress() {
    $result = $this->captcha->isRequired();
    expect($result)->equals(true);
  }

  public function testItTakesFilterIntoAccountToDisableCaptcha() {
    $wp = new WPFunctions;
    $filter = function() {
      return 1;
    };
    $wp->addFilter('mailpoet_subscription_captcha_recipient_limit', $filter);
    $email = 'non-existent-subscriber@example.com';
    $result = $this->captcha->isRequired($email);
    expect($result)->equals(false);

    $result = $this->captcha->isRequired();
    expect($result)->equals(false);

    $subscriberFactory = new SubscriberFactory();
    $subscriber = $subscriberFactory->create();
    $subscriber->setConfirmationsCount(1);
    $result = $this->captcha->isRequired($subscriber->getEmail());
    expect($result)->equals(true);

    $ip = new SubscriberIPEntity('127.0.0.1');
    $ip->setCreatedAt(Carbon::now()->subMinutes(1));
    $this->entityManager->persist($ip);
    $this->entityManager->flush();
    $email = 'non-existent-subscriber@example.com';
    $result = $this->captcha->isRequired($email);
    expect($result)->equals(true);

    $wp->removeFilter('mailpoet_subscription_captcha_recipient_limit', $filter);
  }

  public function testItRendersImageAndStoresHashToSession() {
    expect($this->captchaSession->getCaptchaHash())->false();
    $image = $this->captcha->renderImage(null, null, self::CAPTCHA_SESSION_ID, true);
    expect($image)->notEmpty();
    expect($this->captchaSession->getCaptchaHash())->notEmpty();
  }

  public function _after() {
    $this->truncateEntity(SubscriberIPEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
  }
}

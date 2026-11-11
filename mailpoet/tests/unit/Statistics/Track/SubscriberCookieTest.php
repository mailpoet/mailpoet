<?php declare(strict_types = 1);

namespace MailPoet\Statistics\Track;

use Codeception\Stub;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Util\Cookies;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

class SubscriberCookieTest extends \MailPoetUnitTest {
  private const SALT = 'test-salt';
  private const BLOG_ID = 1;

  /** @var Cookies & MockObject */
  private $cookies;

  public function _before() {
    parent::_before();
    $this->cookies = $this->createMock(Cookies::class);
  }

  public function testItReturnsSubscriberIdFromCookieItCreated(): void {
    $testee = $this->makeTestee();
    $this->setCookies([SubscriberCookie::COOKIE_NAME => $testee->getCookieData(5)]);
    verify($testee->getSubscriberId())->same(5);
  }

  public function testItSetsCookieWithSubscriberIdAndHash(): void {
    $testee = $this->makeTestee();
    $this->cookies->expects($this->once())
      ->method('set')
      ->with(
        SubscriberCookie::COOKIE_NAME,
        ['subscriber_id' => 5, 'hash' => $this->makeHash(5)],
        $this->isType('array')
      );
    $testee->setSubscriberId(5);
  }

  public function testItIgnoresCookieWithoutHash(): void {
    $this->setCookies([SubscriberCookie::COOKIE_NAME => ['subscriber_id' => 5]]);
    verify($this->makeTestee()->getSubscriberId())->null();
  }

  public function testItIgnoresCookieWithInvalidHash(): void {
    $this->setCookies([SubscriberCookie::COOKIE_NAME => ['subscriber_id' => 5, 'hash' => str_repeat('a', 64)]]);
    verify($this->makeTestee()->getSubscriberId())->null();
  }

  public function testItIgnoresCookieWithMismatchedHash(): void {
    $this->setCookies([SubscriberCookie::COOKIE_NAME => ['subscriber_id' => 5, 'hash' => $this->makeHash(6)]]);
    verify($this->makeTestee()->getSubscriberId())->null();
  }

  public function testItIgnoresCookieWithHashFromDifferentSalt(): void {
    $this->setCookies([SubscriberCookie::COOKIE_NAME => ['subscriber_id' => 5, 'hash' => $this->makeHash(5, 'other-salt')]]);
    verify($this->makeTestee()->getSubscriberId())->null();
  }

  public function testItIgnoresCookieFromAnotherSite(): void {
    $this->setCookies([SubscriberCookie::COOKIE_NAME => $this->makeTestee(true, 2)->getCookieData(5)]);
    verify($this->makeTestee()->getSubscriberId())->null();
  }

  public function testItIgnoresCookieWithNonStringHash(): void {
    $this->setCookies([SubscriberCookie::COOKIE_NAME => ['subscriber_id' => 5, 'hash' => ['a']]]);
    verify($this->makeTestee()->getSubscriberId())->null();
  }

  public function testItIgnoresAndDeletesLegacyCookie(): void {
    $this->setCookies([SubscriberCookie::COOKIE_NAME_LEGACY => ['subscriber_id' => 5]]);
    $this->cookies->expects($this->never())->method('set');
    $this->cookies->expects($this->once())->method('delete')->with(SubscriberCookie::COOKIE_NAME_LEGACY);
    verify($this->makeTestee()->getSubscriberId())->null();
  }

  public function testItIgnoresCookieWhenCookieTrackingIsDisabled(): void {
    $testee = $this->makeTestee(false);
    $this->setCookies([SubscriberCookie::COOKIE_NAME => $testee->getCookieData(5)]);
    verify($testee->getSubscriberId())->null();
  }

  private function makeTestee(bool $cookieTrackingEnabled = true, int $blogId = self::BLOG_ID): SubscriberCookie {
    $trackingConfig = Stub::make(TrackingConfig::class, ['isCookieTrackingEnabled' => $cookieTrackingEnabled], $this);
    $wp = Stub::make(WPFunctions::class, ['wpSalt' => self::SALT, 'getCurrentBlogId' => $blogId], $this);
    return new SubscriberCookie($this->cookies, $trackingConfig, $wp);
  }

  private function setCookies(array $cookies): void {
    $this->cookies->method('get')->willReturnCallback(function (string $name) use ($cookies) {
      return $cookies[$name] ?? null;
    });
  }

  private function makeHash(int $subscriberId, string $salt = self::SALT): string {
    return hash_hmac('sha256', 'mailpoet_subscriber|' . self::BLOG_ID . '|' . $subscriberId, $salt);
  }
}

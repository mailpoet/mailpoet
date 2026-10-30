<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use AcceptanceTester;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Statistics\Track\SubscriberCookie;
use MailPoet\Test\DataFactories\Subscriber;
use PHPUnit\Framework\Assert;

class PageViewTrackingCookieCest {
  private const PAGE_VIEW_COOKIE_NAME = 'mailpoet_page_view';

  public function setCookieForSubscriberStoredInCookie(AcceptanceTester $i) {
    $i->wantTo('Check page view cookie is set for subscriber saved in cookie');

    $email = 'test-subscriber@example.com';
    $subscriber = (new Subscriber())->withEmail($email)->create();

    $i->amOnPage('/');
    $i->cantSeeCookie(self::PAGE_VIEW_COOKIE_NAME);
    $cookieData = ContainerWrapper::getInstance()->get(SubscriberCookie::class)->getCookieData((int)$subscriber->getId());
    $i->setCookie('mailpoet_subscriber', json_encode($cookieData));
    $i->reloadPage();

    $i->canSeeCookie(self::PAGE_VIEW_COOKIE_NAME);
    $cookie = $i->grabCookie(self::PAGE_VIEW_COOKIE_NAME);
    Assert::assertIsString($cookie);
    $cookieData = json_decode(urldecode($cookie), true);
    Assert::assertIsArray($cookieData);
    Assert::assertLessThanOrEqual(time(), $cookieData['timestamp']);
    Assert::assertGreaterThan(time() - 10, $cookieData['timestamp']);
  }
}

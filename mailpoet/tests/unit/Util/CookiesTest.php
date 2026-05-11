<?php declare(strict_types = 1);

namespace MailPoet\Test\Util;

use MailPoet\Util\Cookies;

class CookiesTest extends \MailPoetUnitTest {
  public function testDeleteExpiresBrowserCookieAndUnsetsLocalCookie(): void {
    $_COOKIE['mailpoet_test'] = '{"value":"set"}';
    $cookies = $this->createTestableCookies();

    $cookies->delete('mailpoet_test', ['path' => '/']);

    verify($cookies->setCookies)->arrayCount(1);
    verify($cookies->setCookies[0]['name'])->equals('mailpoet_test');
    verify($cookies->setCookies[0]['value'])->equals('');
    verify($cookies->setCookies[0]['options']['expires'])->lessThan(time());
    verify($cookies->setCookies[0]['options']['path'])->equals('/');
    $this->assertArrayNotHasKey('mailpoet_test', $_COOKIE);
  }

  public function testDeleteExpiresDefaultAndRootPathsWhenPathIsNotProvided(): void {
    $_COOKIE['mailpoet_test'] = '{"value":"set"}';
    $cookies = $this->createTestableCookies();

    $cookies->delete('mailpoet_test');

    verify($cookies->setCookies)->arrayCount(2);
    verify($cookies->setCookies[0]['options']['path'])->equals('');
    verify($cookies->setCookies[1]['options']['path'])->equals('/');
    $this->assertArrayNotHasKey('mailpoet_test', $_COOKIE);
  }

  public function testDeleteSkipsBrowserExpirationWhenHeadersWereSent(): void {
    $_COOKIE['mailpoet_test'] = '{"value":"set"}';
    $cookies = $this->createTestableCookies();
    $cookies->headersSent = true;

    $cookies->delete('mailpoet_test', ['path' => '/']);

    verify($cookies->setCookies)->arrayCount(0);
    $this->assertArrayNotHasKey('mailpoet_test', $_COOKIE);
  }

  private function createTestableCookies() {
    return new class extends Cookies {
      /** @var bool */
      public $headersSent = false;

      /** @var array<int, array{name: mixed, value: string, options: array}> */
      public $setCookies = [];

      protected function headersSent(): bool {
        return $this->headersSent;
      }

      protected function setCookie($name, string $value, array $options): void {
        $this->setCookies[] = [
          'name' => $name,
          'value' => $value,
          'options' => $options,
        ];
      }
    };
  }
}

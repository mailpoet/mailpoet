<?php declare(strict_types = 1);

namespace MailPoet\Test\Subscribers;

use MailPoet\Subscribers\SourceUrlResolver;
use MailPoet\WP\Functions as WPFunctions;

class SourceUrlResolverTest extends \MailPoetUnitTest {
  /** @var array */
  private $server;

  public function _before() {
    parent::_before();
    $this->server = $_SERVER;
  }

  public function _after() {
    parent::_after();
    $_SERVER = $this->server;
  }

  public function testItUsesRequestUrlWithoutQueryString(): void {
    $_SERVER['HTTP_HOST'] = 'example.com';
    $_SERVER['REQUEST_URI'] = '/blog/signup/?token=secret&utm_source=x#top';
    verify($this->createResolver(false, false, true)->resolve())->equals('https://example.com/blog/signup/');
    verify($this->createResolver(false, false, false)->resolve())->equals('http://example.com/blog/signup/');
  }

  public function testItPrefersRefererForAjaxRequests(): void {
    $_SERVER['HTTP_HOST'] = 'example.com';
    $_SERVER['REQUEST_URI'] = '/wp-admin/admin-ajax.php';
    $resolver = $this->createResolver(true, 'https://example.com/newsletter/?email=a@b.com#form', false);
    verify($resolver->resolve())->equals('https://example.com/newsletter/');
  }

  public function testItFallsBackToRequestUrlForAjaxRequestsWithoutReferer(): void {
    $_SERVER['HTTP_HOST'] = 'example.com';
    $_SERVER['REQUEST_URI'] = '/wp-admin/admin-ajax.php?action=mailpoet';
    verify($this->createResolver(true, false, true)->resolve())->equals('https://example.com/wp-admin/admin-ajax.php');
  }

  public function testItIgnoresRefererForNonAjaxRequests(): void {
    $_SERVER['HTTP_HOST'] = 'example.com';
    $_SERVER['REQUEST_URI'] = '/checkout/';
    verify($this->createResolver(false, 'https://example.com/cart/', true)->resolve())->equals('https://example.com/checkout/');
  }

  public function testItReturnsNullWithoutARequest(): void {
    unset($_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI']);
    verify($this->createResolver(false, false, false)->resolve())->null();
  }

  public function testItRejectsUnsupportedSchemes(): void {
    $resolver = $this->createResolver(true, 'javascript:alert(1)', false);
    unset($_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI']);
    verify($resolver->resolve())->null();
  }

  public function testItTruncatesLongUrls(): void {
    $_SERVER['HTTP_HOST'] = 'example.com';
    $_SERVER['REQUEST_URI'] = '/' . str_repeat('a', 3000);
    verify(strlen((string)$this->createResolver(false, false, true)->resolve()))->equals(2048);
  }

  /**
   * @param string|false $referer
   */
  private function createResolver(bool $isAjax, $referer, bool $isSsl): SourceUrlResolver {
    $wp = $this->createMock(WPFunctions::class);
    $wp->method('wpDoingAjax')->willReturn($isAjax);
    $wp->method('wpGetReferer')->willReturn($referer);
    $wp->method('isSsl')->willReturn($isSsl);
    $wp->method('wpUnslash')->willReturnArgument(0);
    $wp->method('escUrlRaw')->willReturnCallback(function (string $url, ?array $protocols = null): string {
      $scheme = parse_url($url, PHP_URL_SCHEME);
      return in_array($scheme, $protocols ?? ['http', 'https'], true) ? $url : '';
    });
    return new SourceUrlResolver($wp);
  }
}

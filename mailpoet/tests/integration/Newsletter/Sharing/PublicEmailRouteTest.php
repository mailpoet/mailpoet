<?php declare(strict_types = 1);

namespace MailPoet\Test\Newsletter\Sharing;

use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Newsletter\Sharing\PublicEmailController;
use MailPoet\Newsletter\Sharing\PublicEmailRoute;
use MailPoet\Newsletter\Sharing\ShareVisibility;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\WP\Functions as WPFunctions;
use ReflectionMethod;

class PublicEmailRouteTest extends \MailPoetTest {
  /** @var string|null */
  private $previousRequestUri;

  /** @var string|false */
  private $previousHomeOption;

  /** @var string|false */
  private $previousPermalinkStructureOption;

  public function _before() {
    parent::_before();
    $wp = $this->diContainer->get(WPFunctions::class);
    $uri = $_SERVER['REQUEST_URI'] ?? null;
    $this->previousRequestUri = is_string($uri) ? $uri : null;
    $this->previousHomeOption = $wp->getOption('home');
    $this->previousPermalinkStructureOption = $wp->getOption('permalink_structure');
  }

  public function _after() {
    if ($this->previousRequestUri === null) {
      unset($_SERVER['REQUEST_URI']);
    } else {
      $_SERVER['REQUEST_URI'] = $this->previousRequestUri;
    }
    $wp = $this->diContainer->get(WPFunctions::class);
    if (is_string($this->previousHomeOption)) {
      $wp->updateOption('home', $this->previousHomeOption);
    } else {
      $wp->deleteOption('home');
    }
    if (is_string($this->previousPermalinkStructureOption)) {
      $wp->updateOption('permalink_structure', $this->previousPermalinkStructureOption);
    } else {
      $wp->deleteOption('permalink_structure');
    }
    parent::_after();
  }

  public function testItReadsIdentifierFromPrettyRequestPath() {
    $_SERVER['REQUEST_URI'] = '/mailpoet-email/abc123def456-spring-sale/';

    verify($this->invokeGetIdentifier())->equals('abc123def456-spring-sale');
  }

  public function testItReadsIdentifierWhenHomeUrlLivesInSubdirectory() {
    $wp = $this->diContainer->get(WPFunctions::class);
    $wp->updateOption('home', 'http://example.com/blog');
    $_SERVER['REQUEST_URI'] = '/blog/mailpoet-email/abc123def456-spring-sale/';

    verify($this->invokeGetIdentifier())->equals('abc123def456-spring-sale');
  }

  public function testItIgnoresRequestPathsOutsideThePublicSharePrefix() {
    $_SERVER['REQUEST_URI'] = '/some-other-page/abc123def456/';

    verify($this->invokeGetIdentifier())->equals('');
  }

  public function testItIgnoresMalformedIdentifiers() {
    $_SERVER['REQUEST_URI'] = '/mailpoet-email/not a valid identifier!/';

    verify($this->invokeGetIdentifier())->equals('');
  }

  public function testItIgnoresOrdinarySlugsThatLookLikePageNames() {
    // A site that already has a page at this URL must not get hijacked.
    $_SERVER['REQUEST_URI'] = '/mailpoet-email/about/';

    verify($this->invokeGetIdentifier())->equals('');
  }

  public function testItRedirectsNonCanonicalIdentifierToCanonicalUrl() {
    $wp = $this->diContainer->get(WPFunctions::class);
    $wp->updateOption('permalink_structure', '/%postname%/');
    $newsletter = (new Newsletter())
      ->withSubject('Spring Sale')
      ->withSentStatus()
      ->withSendingQueue()
      ->withOptions([
        NewsletterOptionFieldEntity::NAME_SHARE_VISIBILITY => ShareVisibility::VISIBILITY_PUBLIC,
      ])
      ->create();
    $hash = (string)$newsletter->getHash();
    $_SERVER['REQUEST_URI'] = '/mailpoet-email/' . $hash . '-stale-slug/';

    $route = $this->buildRouteWithRedirectInterceptor($capturedUrl);

    try {
      $route->render();
      $this->fail('Expected redirect to interrupt execution');
    } catch (\RuntimeException $e) {
      verify($e->getMessage())->equals('exit_redirect');
    }
    verify($capturedUrl)->stringContainsString(sprintf('/mailpoet-email/%s-spring-sale/', $hash));
  }

  public function testItReturnsSilentlyWhenIdentifierDoesNotResolveToNewsletter() {
    // The path matches our share prefix but no newsletter has this hash.
    // We must not 404 — a real page could live at this URL on the site.
    $_SERVER['REQUEST_URI'] = '/mailpoet-email/000000000000-missing/';

    $route = $this->buildRouteWithSilentExpectations();

    $route->render();
    // No exception, no redirect, no status header — the test would have
    // failed on the `expects($this->never())` calls inside the mock if we
    // had taken either branch.
  }

  private function invokeGetIdentifier(): string {
    $route = $this->diContainer->get(PublicEmailRoute::class);
    $method = new ReflectionMethod($route, 'getIdentifier');
    $method->setAccessible(true);
    $result = $method->invoke($route);
    return is_string($result) ? $result : '';
  }

  private function buildRouteWithRedirectInterceptor(&$capturedUrl): PublicEmailRoute {
    $wp = $this->createMock(WPFunctions::class);
    $wp->method('getQueryVar')->willReturn('');
    $wp->method('wpUnslash')->willReturnArgument(0);
    $wp->method('sanitizeTextField')->willReturnArgument(0);
    $wp->method('wpParseUrl')->willReturnCallback(function ($url, $component) {
      return parse_url((string)$url, $component);
    });
    $wp->method('homeUrl')->willReturn('http://example.com/');
    $wp->method('applyFilters')->willReturnArgument(1);
    $wp->method('wpSafeRedirect')->willReturnCallback(function ($url) use (&$capturedUrl) {
      $capturedUrl = $url;
      throw new \RuntimeException('exit_redirect');
    });
    return new PublicEmailRoute(
      $this->diContainer->get(PublicEmailController::class),
      new NewsletterUrl($this->diContainer->get(\MailPoet\Subscribers\LinkTokens::class), $wp),
      $wp
    );
  }

  private function buildRouteWithSilentExpectations(): PublicEmailRoute {
    $wp = $this->createMock(WPFunctions::class);
    $wp->method('getQueryVar')->willReturn('');
    $wp->method('wpUnslash')->willReturnArgument(0);
    $wp->method('sanitizeTextField')->willReturnArgument(0);
    $wp->method('wpParseUrl')->willReturnCallback(function ($url, $component) {
      return parse_url((string)$url, $component);
    });
    $wp->method('homeUrl')->willReturn('http://example.com/');
    $wp->method('applyFilters')->willReturnArgument(1);
    $wp->expects($this->never())->method('wpSafeRedirect');
    $wp->expects($this->never())->method('statusHeader');
    return new PublicEmailRoute(
      $this->diContainer->get(PublicEmailController::class),
      new NewsletterUrl($this->diContainer->get(\MailPoet\Subscribers\LinkTokens::class), $wp),
      $wp
    );
  }
}

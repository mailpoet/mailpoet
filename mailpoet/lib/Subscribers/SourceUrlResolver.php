<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\WP\Functions as WPFunctions;

class SourceUrlResolver {
  private const MAX_LENGTH = 2048;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  /**
   * Returns the URL, without query string and fragment, the subscriber was created from.
   * AJAX and REST requests hit an endpoint, so for those the page that made the request (referer) is preferred.
   */
  public function resolve(): ?string {
    if ($this->isAjaxOrRestRequest()) {
      $url = $this->fromReferer();
      if ($url) {
        return $url;
      }
    }
    return $this->fromRequest();
  }

  private function isAjaxOrRestRequest(): bool {
    return $this->wp->wpDoingAjax() || (defined('REST_REQUEST') && constant('REST_REQUEST'));
  }

  private function fromReferer(): ?string {
    $referer = $this->wp->wpGetReferer();
    return is_string($referer) ? $this->sanitize($referer) : null;
  }

  private function fromRequest(): ?string {
    $host = $this->getServerValue('HTTP_HOST');
    $requestUri = $this->getServerValue('REQUEST_URI');
    if (!$host || !$requestUri) {
      return null;
    }
    $scheme = $this->wp->isSsl() ? 'https' : 'http';
    return $this->sanitize($scheme . '://' . $host . $requestUri);
  }

  private function getServerValue(string $key): ?string {
    $value = $_SERVER[$key] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- the URL is sanitized by escUrlRaw in sanitize().
    // WordPress adds slashes to superglobals
    return is_string($value) ? stripslashes($value) : null;
  }

  private function sanitize(string $url): ?string {
    $withoutQuery = explode('#', explode('?', $url)[0])[0];
    $sanitized = $this->wp->escUrlRaw($withoutQuery, ['http', 'https']);
    return $sanitized !== '' ? substr($sanitized, 0, self::MAX_LENGTH) : null;
  }
}

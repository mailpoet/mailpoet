<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sharing;

use InvalidArgumentException;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\WP\Functions as WPFunctions;

class PublicEmailRoute {
  public const QUERY_VAR = 'mailpoet_public_email';
  private const IDENTIFIER_CAPTURE = '([0-9a-f]{12}(?:-[^/]+)?)';

  /** @var PublicEmailController */
  private $controller;

  /** @var NewsletterUrl */
  private $newsletterUrl;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    PublicEmailController $controller,
    NewsletterUrl $newsletterUrl,
    WPFunctions $wp
  ) {
    $this->controller = $controller;
    $this->newsletterUrl = $newsletterUrl;
    $this->wp = $wp;
  }

  public function init(): void {
    $this->wp->addAction('init', [$this, 'registerRewriteRule']);
    $this->wp->addFilter('query_vars', [$this, 'addQueryVars']);
    $this->wp->addAction('template_redirect', [$this, 'render']);
  }

  public function registerRewriteRule(): void {
    $prefix = ltrim($this->newsletterUrl->getPublicSharePathPrefix(), '/');
    $this->wp->addRewriteRule(
      '^' . $prefix . self::IDENTIFIER_CAPTURE . '/?$',
      'index.php?' . self::QUERY_VAR . '=$matches[1]',
      'top'
    );
  }

  public function addQueryVars(array $queryVars): array {
    $queryVars[] = self::QUERY_VAR;
    return $queryVars;
  }

  public function render(): void {
    $identifier = $this->getIdentifier();
    if ($identifier === '') {
      return;
    }

    try {
      $newsletter = $this->controller->getNewsletter($identifier);
    } catch (InvalidArgumentException $e) {
      // The path matched our prefix but the identifier didn't resolve to a
      // shareable newsletter. Let WP continue: it will serve a real page at
      // that URL if one exists, or fall through to its own 404.
      return;
    }
    if (!$this->controller->isCanonicalIdentifier($identifier, $newsletter)) {
      $this->wp->wpSafeRedirect($this->controller->getCanonicalUrl($newsletter), 301);
      exit;
    }
    $this->display($this->controller->render($newsletter));
  }

  private function getIdentifier(): string {
    $identifier = $this->wp->getQueryVar(self::QUERY_VAR, '');
    if (is_string($identifier) && trim($identifier) !== '') {
      return trim($identifier);
    }

    return $this->getIdentifierFromRequestPath();
  }

  private function getIdentifierFromRequestPath(): string {
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized through WPFunctions below.
    $rawUri = $_SERVER['REQUEST_URI'] ?? '';
    if (!is_string($rawUri) || $rawUri === '') {
      return '';
    }
    $unslashed = $this->wp->wpUnslash($rawUri);
    if (!is_string($unslashed)) {
      return '';
    }
    $requestUri = $this->wp->sanitizeTextField($unslashed);
    if (!is_string($requestUri) || $requestUri === '') {
      return '';
    }

    $requestPath = $this->wp->wpParseUrl($requestUri, PHP_URL_PATH);
    if (!is_string($requestPath)) {
      return '';
    }

    $homePath = $this->wp->wpParseUrl($this->wp->homeUrl('/'), PHP_URL_PATH);
    if (is_string($homePath) && $homePath !== '/') {
      $homePath = rtrim($homePath, '/');
      if (strpos($requestPath, $homePath . '/') === 0) {
        $requestPath = substr($requestPath, strlen($homePath));
      }
    }

    $pathPrefix = $this->newsletterUrl->getPublicSharePathPrefix();
    if (strpos($requestPath, $pathPrefix) !== 0) {
      return '';
    }

    $identifier = trim(substr($requestPath, strlen($pathPrefix)), '/');
    if ($identifier === '' || strpos($identifier, '/') !== false) {
      return '';
    }

    return $this->newsletterUrl->parsePublicShareIdentifier($identifier) ? $identifier : '';
  }

  private function display(string $html): void {
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: private, no-store, max-age=0');
    header('X-Robots-Tag: noindex, nofollow');
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $html;
    exit;
  }
}

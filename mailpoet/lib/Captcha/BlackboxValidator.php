<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use MailPoet\Logging\LoggerFactory;
use MailPoet\Services\Bridge;
use MailPoet\Util\Helpers;
use Throwable;

/**
 * Verifies a Blackbox session against MailPoet's bridge and reduces the result to a
 * single decision string. Never throws: any failure (network, timeout, malformed
 * response, rejected key) is logged and reported back as 'error' so callers can
 * implement a fail-open policy without their own try/catch.
 */
class BlackboxValidator {
  const DECISION_ALLOW = 'allow';
  const DECISION_CHALLENGE = 'challenge';
  const DECISION_BLOCK = 'block';
  const DECISION_ERROR = 'error';

  /** @var Bridge */
  private $bridge;

  /** @var LoggerFactory */
  private $loggerFactory;

  public function __construct(
    Bridge $bridge,
    LoggerFactory $loggerFactory
  ) {
    $this->bridge = $bridge;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * $sessionId is null whenever the JS client never produced one: JS disabled, the
   * script blocked, or — for MailPoet specifically — any submission that didn't go
   * through the JS-intercepted AJAX flow at all, such as the native admin-post.php
   * fallback (cross-origin iframe embeds, the no-JS CAPTCHA-resubmit page). That's a
   * common path, not an edge case, so it takes Blackbox's no-session fallback (scored
   * from the visitor's IP/headers) rather than being skipped.
   */
  public function verify(?string $sessionId, array $context = []): string {
    try {
      $visitorIp = $sessionId === null ? Helpers::getIP() : null;
      if ($sessionId === null && empty($visitorIp)) {
        // No session and no IP to fall back on: nothing to verify against.
        return self::DECISION_ERROR;
      }
      $result = $this->bridge->verifyBlackbox($sessionId, $context, $visitorIp);
      return $result['decision'] ?? self::DECISION_ERROR;
    } catch (Throwable $e) {
      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_BRIDGE)->error(
        'Blackbox verify failed, failing open.',
        ['error' => $e->getMessage()]
      );
      return self::DECISION_ERROR;
    }
  }
}

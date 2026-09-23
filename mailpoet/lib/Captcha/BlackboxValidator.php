<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use MailPoet\Logging\LoggerFactory;
use MailPoet\Services\Bridge;
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

  public function verify(?string $sessionId, array $context = []): string {
    try {
      $result = $this->bridge->verifyBlackbox($sessionId, $context);
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

<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Coupons;

class CouponBlockGenerationFailureCollector {
  /** @var array<int, array{code: string, message: string, attrs: array, context: array}> */
  private $failures = [];

  public function clear(): void {
    $this->failures = [];
  }

  public function record(string $code, string $message, array $attrs, array $context): void {
    $this->failures[] = [
      'code' => $code,
      'message' => $message,
      'attrs' => $attrs,
      'context' => $context,
    ];
  }

  public function hasFailures(): bool {
    return $this->failures !== [];
  }

  public function getFailures(): array {
    return $this->failures;
  }
}

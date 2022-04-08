<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\Core\Actions\Wait;

use MailPoet\Automation\Engine\Workflows\AbstractValidationResult;

class ValidationResult extends AbstractValidationResult {

  /**
   * @var int
   */
  private $waitTime;

  public function setWaitTime(int $seconds): void {
    $this->waitTime = $seconds;
  }

  public function isValid(): bool {
    return is_int($this->getWaitTime()) && $this->getWaitTime() > 0;
  }

  public function getWaitTime(): int {
    return $this->waitTime;
  }
}

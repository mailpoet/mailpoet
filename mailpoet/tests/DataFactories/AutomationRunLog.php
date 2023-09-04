<?php declare(strict_types = 1);

namespace MailPoet\Test\DataFactories;

use Exception;
use MailPoet\Automation\Engine\Data\AutomationRunLog as AutomationRunLogData;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Storage\AutomationRunLogStorage;
use MailPoet\DI\ContainerWrapper;
use Throwable;

class AutomationRunLog {
  /** @var AutomationRunLogData */
  private $log;

  public function __construct(
    int $automationRunId,
    Step $step
  ) {
    $this->log = new AutomationRunLogData(
      $automationRunId,
      $step->getId(),
      $step->getType()
    );
    $this->log->setStepKey($step->getKey());
    $this->log->setStatus(AutomationRunLogData::STATUS_COMPLETE);
  }

  public function setStatus(string $status): self {
    $this->log->setStatus($status);
    return $this;
  }

  public function withData(string $key, array $data): self {
    $this->log->setData($key, $data);
    return $this;
  }

  public function withError(Throwable $error): self {
    $this->log->setError($error);
    return $this;
  }

  public function create(): AutomationRunLogData {
    $storage = ContainerWrapper::getInstance()->get(AutomationRunLogStorage::class);
    $id = $storage->createAutomationRunLog($this->log);
    $log = $storage->getAutomationRunLog($id);
    if (!$log) {
      throw new Exception('Log not found.');
    }
    $this->log = $log;
    return $this->log;
  }
}

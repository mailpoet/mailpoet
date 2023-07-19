<?php declare(strict_types = 1);

namespace MailPoet\Test\DataFactories;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun as Entity;
use MailPoet\Automation\Engine\Data\AutomationRunLog;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Storage\AutomationRunLogStorage;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\DI\ContainerWrapper;

class AutomationRun {


  /** @var ?Entity */
  private $automationRun = null;

  /** @var Automation  */
  private $automation;

  /** @var array Subject[] */
  private $subjects = [];

  /** @var string  */
  private $triggerKey = '';

  /** @var \DateTimeImmutable | null */
  private $createdAt;

  /** @var \DateTimeImmutable | null */
  private $updatedAt;

  /** @var string  */
  private $status = Entity::STATUS_COMPLETE;

  /** @var ?string */
  private $nextStep = null;

  private $storage;

  public function __construct() {
    $this->storage = ContainerWrapper::getInstance(WP_DEBUG)->get(AutomationRunStorage::class);
  }

  public function withAutomation(Automation $automation): self {
    $this->automation = $automation;
    return $this;
  }

  public function withSubject(Subject $subject): self {
    $this->subjects[] = $subject;
    return $this;
  }

  public function withTriggerKey(string $triggeKey): self {
    $this->triggerKey = $triggeKey;
    return $this;
  }

  public function withCreatedAt(DateTimeImmutable $createdAt): self {
    $this->createdAt = $createdAt;
    if (!$this->updatedAt) {
      return $this->withUpdatedAt($createdAt);
    }
    return $this;
  }

  public function withUpdatedAt(DateTimeImmutable $updatedAt): self {
    $this->updatedAt = $updatedAt;
    if (!$this->createdAt) {
      return $this->withCreatedAt($updatedAt);
    }
    return $this;
  }

  public function withStatus(string $status): self {
    $this->status = $status;
    return $this;
  }

  public function withNextStep(string $nextStep = null): self {
    $this->nextStep = $nextStep;
    return $this;
  }

  public function create(): Entity {

    $automationRun = new Entity(
      $this->automation->getId(),
      $this->automation->getVersionId(),
      $this->triggerKey,
      $this->subjects
    );

    $id = $this->storage->createAutomationRun($automationRun);
    $automationRun = $this->storage->getAutomationRun($id);
    $this->storage->deleteAutomationRun($automationRun);

    $array = $automationRun->toArray();
    $array['id'] = $automationRun->getId();
    $array['status'] = $this->status;
    if ($this->createdAt) {
      $array['created_at'] = $this->createdAt->format(DateTimeImmutable::W3C);
    }
    if ($this->updatedAt) {
      $array['updated_at'] = $this->updatedAt->format(DateTimeImmutable::W3C);
    }
    $automationRun = Entity::fromArray($array);
    $id = $this->storage->createAutomationRun($automationRun);
    $this->storage->updateNextStep($id, $this->nextStep);
    $this->automationRun = $this->storage->getAutomationRun($id);
    return $this->automationRun;
  }

  public function generateLogs(Entity $run, string $lastStep) {
    $automation = ContainerWrapper::getInstance(WP_DEBUG)->get(AutomationStorage::class)->getAutomation($run->getAutomationId());
    $steps = $this->findPathToStep($automation, $lastStep);

    $logStorage = ContainerWrapper::getInstance(WP_DEBUG)->get(AutomationRunLogStorage::class);
    foreach ($steps as $step) {
      $log = new AutomationRunLog($run->getId(), $step);
      $log->markCompletedSuccessfully();
      $logStorage->createAutomationRunLog($log);
    }
  }

  private function findPathToStep(Automation $automation, $lastStep): array {

    $steps = $automation->getSteps();
    if (in_array($steps[$lastStep]->getType(), [Step::TYPE_ROOT, Step::TYPE_TRIGGER], true)) {
      return [];
    }
    $steps = [];
    foreach ($automation->getSteps() as $step) {
      $nextStepIds = array_map(
        function(NextStep $next): string { return $next->getId();
        },
        $step->getNextSteps()
      );
      if (!in_array($lastStep, $nextStepIds, true)) {
        continue;
      }

      $steps = array_merge($this->findPathToStep($automation, $step->getId()), [$step->getId()]);
    }

    return array_unique(array_merge($steps, [$lastStep]));

  }
}

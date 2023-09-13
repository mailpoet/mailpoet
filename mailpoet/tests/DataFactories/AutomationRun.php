<?php declare(strict_types = 1);

namespace MailPoet\Test\DataFactories;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun as Entity;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\DI\ContainerWrapper;
use MailPoet\InvalidStateException;

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
    $now = new DateTimeImmutable();
    $automationRun = Entity::fromArray([
      'id' => 0,
      'automation_id' => $this->automation->getId(),
      'version_id' => $this->automation->getVersionId(),
      'trigger_key' => $this->triggerKey,
      'status' => $this->status,
      'created_at' => ($this->createdAt ?? $now)->format(DateTimeImmutable::W3C),
      'updated_at' => ($this->updatedAt ?? $now)->format(DateTimeImmutable::W3C),
      'subjects' => array_map(function (Subject $subject) {
        return $subject->toArray();
      }, $this->subjects),
    ]);

    $id = $this->storage->createAutomationRun($automationRun);
    $this->storage->updateNextStep($id, $this->nextStep);
    $this->automationRun = $this->storage->getAutomationRun($id);
    if (!$this->automationRun) {
      throw new InvalidStateException();
    }
    return $this->automationRun;
  }
}

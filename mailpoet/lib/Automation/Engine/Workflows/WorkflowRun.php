<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Workflows;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Exceptions\InvalidStateException;
use MailPoet\Automation\Engine\Utils\Json;

class WorkflowRun {
  public const STATUS_RUNNING = 'running';
  public const STATUS_COMPLETE = 'complete';
  public const STATUS_CANCELLED = 'cancelled';
  public const STATUS_FAILED = 'failed';

  /** @var int */
  private $id;

  /** @var int */
  private $workflowId;

  /** @var string */
  private $triggerKey;

  /** @var string */
  private $status = self::STATUS_RUNNING;

  /** @var DateTimeImmutable */
  private $createdAt;

  /** @var DateTimeImmutable */
  private $updatedAt;

  /** @var Subject[] */
  private $subjects;

  /** @var array<class-string, string> */
  private $subjectKeyClassMap = [];

  /**
   * @param Subject[] $subjects
   */
  public function __construct(
    int $workflowId,
    string $triggerKey,
    array $subjects,
    int $id = null
  ) {
    $this->workflowId = $workflowId;
    $this->triggerKey = $triggerKey;
    $this->subjects = $subjects;

    foreach ($subjects as $subject) {
      $this->subjectKeyClassMap[get_class($subject)] = $subject->getKey();
    }

    if ($id) {
      $this->id = $id;
    }

    $now = new DateTimeImmutable();
    $this->createdAt = $now;
    $this->updatedAt = $now;
  }

  public function getId(): int {
    return $this->id;
  }

  public function getWorkflowId(): int {
    return $this->workflowId;
  }

  public function getTriggerKey(): string {
    return $this->triggerKey;
  }

  public function getStatus(): string {
    return $this->status;
  }

  public function getCreatedAt(): DateTimeImmutable {
    return $this->createdAt;
  }

  public function getUpdatedAt(): DateTimeImmutable {
    return $this->updatedAt;
  }

  /** @return Subject[] */
  public function getSubjects(string $key = null): array {
    if ($key) {
      return array_values(
        array_filter($this->subjects, function (Subject $subject) use ($key) {
          return $subject->getKey() === $key;
        })
      );
    }
    return $this->subjects;
  }

  /**
   * @template T of Subject
   * @param class-string<T> $class
   * @return T
   */
  public function requireSingleSubject(string $class): Subject {
    $key = $this->subjectKeyClassMap[$class] ?? null;
    if (!$key) {
      throw Exceptions::subjectClassNotFound($class);
    }

    $subjects = $this->getSubjects($key);
    if (count($subjects) === 0) {
      throw Exceptions::subjectNotFound($key);
    }
    if (count($subjects) > 1) {
      throw Exceptions::multipleSubjectsFound($key);
    }

    // ensure PHPStan we're indeed returning an instance of $class
    $subject = $subjects[0];
    if (!$subject instanceof $class) {
      throw new InvalidStateException();
    }
    return $subject;
  }

  public function toArray(): array {
    return [
      'workflow_id' => $this->workflowId,
      'trigger_key' => $this->triggerKey,
      'status' => $this->status,
      'created_at' => $this->createdAt->format(DateTimeImmutable::W3C),
      'updated_at' => $this->updatedAt->format(DateTimeImmutable::W3C),
      'subjects' => Json::encode(
        array_map(function (Subject $subject): array {
          return ['key' => $subject->getKey(), 'args' => $subject->pack()];
        }, $this->subjects)
      ),
    ];
  }

  public static function fromArray(array $data): self {
    $workflowRun = new WorkflowRun((int)$data['workflow_id'], $data['trigger_key'], $data['subjects']);
    $workflowRun->id = (int)$data['id'];
    $workflowRun->status = $data['status'];
    $workflowRun->createdAt = $data['created_at'];
    $workflowRun->updatedAt = $data['updated_at'];
    return $workflowRun;
  }
}

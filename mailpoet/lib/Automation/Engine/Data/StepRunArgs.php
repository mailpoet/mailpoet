<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Data;

use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Workflows\Payload;
use MailPoet\Automation\Engine\Workflows\Subject;

class StepRunArgs {
  /** @var Workflow */
  private $workflow;

  /** @var WorkflowRun */
  private $workflowRun;

  /** @var Step */
  private $step;

  /** @var array<string, SubjectEntry<Subject<Payload>>[]> */
  private $subjectEntries = [];

  /** @var array<class-string, string> */
  private $subjectKeyClassMap = [];

  /** @param SubjectEntry<Subject<Payload>>[] $subjectsEntries */
  public function __construct(
    Workflow $workflow,
    WorkflowRun $workflowRun,
    Step $step,
    array $subjectsEntries
  ) {
    $this->workflow = $workflow;
    $this->step = $step;
    $this->workflowRun = $workflowRun;

    foreach ($subjectsEntries as $entry) {
      $subject = $entry->getSubject();
      $key = $subject->getKey();
      $this->subjectEntries[$key] = array_merge($this->subjectEntries[$key] ?? [], [$entry]);
      $this->subjectKeyClassMap[get_class($subject)] = $key;
    }
  }

  public function getWorkflow(): Workflow {
    return $this->workflow;
  }

  public function getWorkflowRun(): WorkflowRun {
    return $this->workflowRun;
  }

  public function getStep(): Step {
    return $this->step;
  }

  /** @return SubjectEntry<Subject<Payload>> */
  public function getSingleSubjectEntry(string $key): SubjectEntry {
    $subjects = $this->subjectEntries[$key] ?? [];
    if (count($subjects) === 0) {
      throw Exceptions::subjectDataNotFound($key, $this->workflowRun->getId());
    }
    if (count($subjects) > 1) {
      throw Exceptions::multipleSubjectsFound($key, $this->workflowRun->getId());
    }
    return $subjects[0];
  }

  /**
   * @template P of Payload
   * @template S of Subject<P>
   * @param class-string<S> $class
   * @return SubjectEntry<S<P>>
   */
  public function getSingleSubjectEntryByClass(string $class): SubjectEntry {
    $key = $this->subjectKeyClassMap[$class] ?? null;
    if (!$key) {
      throw Exceptions::subjectClassNotFound($class);
    }

    /** @var SubjectEntry<S<P>> $entry -- for PHPStan */
    $entry = $this->getSingleSubjectEntry($key);
    return $entry;
  }
}

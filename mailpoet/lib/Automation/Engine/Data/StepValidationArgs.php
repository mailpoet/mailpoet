<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Data;

use MailPoet\Automation\Engine\Exceptions;
use MailPoet\Automation\Engine\Integration\Payload;
use MailPoet\Automation\Engine\Integration\Subject;

class StepValidationArgs {
  /** @var Workflow */
  private $workflow;

  /** @var Step */
  private $step;

  /** @var array<string, Subject<Payload>> */
  private $subjects = [];

  /** @var array<class-string, string> */
  private $subjectKeyClassMap = [];

  /** @param Subject<Payload>[] $subjects */
  public function __construct(
    Workflow $workflow,
    Step $step,
    array $subjects
  ) {
    $this->workflow = $workflow;
    $this->step = $step;

    foreach ($subjects as $subject) {
      $key = $subject->getKey();
      $this->subjects[$key] = $subject;
      $this->subjectKeyClassMap[get_class($subject)] = $key;
    }
  }

  public function getWorkflow(): Workflow {
    return $this->workflow;
  }

  public function getStep(): Step {
    return $this->step;
  }

  /** @return Subject<Payload>[] */
  public function getSubjects(): array {
    return array_values($this->subjects);
  }

  /** @return Subject<Payload> */
  public function getSingleSubject(string $key): Subject {
    $subject = $this->subjects[$key] ?? null;
    if (!$subject) {
      throw Exceptions::subjectNotFound($key);
    }
    return $subject;
  }

  /**
   * @template P of Payload
   * @template S of Subject<P>
   * @param class-string<S> $class
   * @return S<P>
   */
  public function getSingleSubjectByClass(string $class): Subject {
    $key = $this->subjectKeyClassMap[$class] ?? null;
    if (!$key) {
      throw Exceptions::subjectClassNotFound($class);
    }

    /** @var S<P> $subject -- for PHPStan */
    $subject = $this->getSingleSubject($key);
    return $subject;
  }
}

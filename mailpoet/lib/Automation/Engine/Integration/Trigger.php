<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Integration;

use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\SubjectEntry;

interface Trigger extends Step {
  public function registerHooks(): void;

  public function isTriggeredBy(StepRunArgs $args): bool;

  /**
   * @param SubjectEntry<Subject<Payload>>[] $subjectEntries
   * @return string
   */
  public function getSubjectHash(array $subjectEntries): string;
}

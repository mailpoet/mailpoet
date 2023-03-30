<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Control;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Step as StepData;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Integration\Step;
use MailPoet\Automation\Engine\Integration\SubjectTransformer;
use MailPoet\Automation\Engine\Integration\Trigger;
use MailPoet\Automation\Engine\Registry;

class SubjectTransformerHandler {

  /* @var Registry */
  private $registry;

  public function __construct(
    Registry $registry
  ) {
    $this->registry = $registry;
  }

  public function subjectKeysForTrigger(Trigger $trigger): array {
    $subjectKeys = $trigger->getSubjectKeys();
    $possibleKeys = [];
    foreach ($subjectKeys as $key) {
      $possibleKeys = array_merge($possibleKeys, $this->getPossibleTransformations($key));
    }
    return array_unique(array_values(array_merge($subjectKeys, $possibleKeys)));
  }

  /** @return string[] */
  public function subjectKeysForAutomation(Automation $automation): array {
    $configuredTriggers = array_filter(
      $automation->getSteps(),
      function (StepData $step): bool {
        return $step->getType() === StepData::TYPE_TRIGGER;
      }
    );

    $triggers = array_filter(array_map(
      function(StepData $step): ?Step {
        return $this->registry->getStep($step->getKey());
      },
      $configuredTriggers
    ));

    $triggerKeys = [];
    foreach ($triggers as $trigger) {
      $triggerKeys[$trigger->getKey()] = $trigger->getSubjectKeys();
    }
    if (!$triggerKeys) {
      return [];
    }

    $triggerKeys = count($triggerKeys) === 1 ? current($triggerKeys) : array_intersect(...array_values($triggerKeys));
    $possibleKeys = [];
    foreach ($triggerKeys as $key) {
      $possibleKeys = array_merge($possibleKeys, $this->getPossibleTransformations($key));
    }
    return array_unique(array_values(array_merge($triggerKeys, $possibleKeys)));
  }

  private function getPossibleTransformations(string $key, string $stopKey = null): array {
    if (!$stopKey) {
      $stopKey = $key;
    }
    $allTransformer = $this->registry->getSubjectTransformer();
    $possibleTransformer = array_filter(
      $allTransformer,
      function (SubjectTransformer $transformer) use ($key): bool {
        return $transformer->accepts() === $key;
      }
    );

    $possibleKeys = [];
    foreach ($possibleTransformer as $transformer) {
      $possibleKey = $transformer->returns();
      if ($possibleKey === $stopKey) {
        continue;
      }
      $possibleKeys[$possibleKey] = $possibleKey;
      $possibleKeys = array_merge(
        $possibleKeys,
        $this->getPossibleTransformations($possibleKey, $stopKey)
      );
    }
    return $possibleKeys;
  }

  /**
   * @param Trigger $trigger
   * @param Subject ...$subjects
   * @return Subject[]
   */
  public function provideAllSubjects(Trigger $trigger, Subject ...$subjects): array {
    $allSubjectsKeys = $this->subjectKeysForTrigger($trigger);
    $allSubjectKeyTargets = array_diff($allSubjectsKeys, array_map(
      function(Subject $subject): string {
        return $subject->getKey();
      },
      $subjects
    ));

    $allSubjects = [];
    foreach ($subjects as $existingSubject) {
      $allSubjects[$existingSubject->getKey()] = $existingSubject;
    }
    foreach ($allSubjectKeyTargets as $target) {
      if (isset($allSubjects[$target])) {
        continue;
      }
      foreach ($subjects as $subject) {
        $transformedSubject = $this->transformSubjectTo($subject, $target);
        if ($transformedSubject) {
          $allSubjects[$transformedSubject->getKey()] = $transformedSubject;
        }
      }
    }
    while (count($allSubjects) < count($allSubjectsKeys)) {
      $allSubjects = $this->provideAllSubjects($trigger, ...array_values($allSubjects));
    }
    return array_values($allSubjects);
  }

  private function transformSubjectTo(Subject $subject, string $target): ?Subject {
    $transformerChain = $this->getTransformerChain($subject->getKey(), $target);
    if (!$transformerChain) {
      return null;
    }
    foreach ($transformerChain as $transformer) {
      $subject = $transformer->transform($subject);
    }
    return $subject;
  }

  /**
   * @return SubjectTransformer[]
   */
  private function getTransformerChain(string $from, string $to): array {
    $transformers = $this->registry->getSubjectTransformer();
    $transformerChain = [];

    //walk the graph of transformers to find the shortest path
    $queue = [];
    $queue[] = [$from, []];
    while (count($queue) > 0) {
      $current = array_shift($queue);
      $currentKey = $current[0];
      $currentPath = $current[1];
      if ($currentKey === $to) {
        $transformerChain = $currentPath;
        break;
      }
      foreach ($transformers as $transformer) {
        if ($transformer->accepts() === $currentKey) {
          $newPath = $currentPath;
          $newPath[] = $transformer;
          $queue[] = [$transformer->returns(), $newPath];
        }
      }
    }

    return $transformerChain;
  }
}

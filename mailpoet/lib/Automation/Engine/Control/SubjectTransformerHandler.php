<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Control;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Step as StepData;
use MailPoet\Automation\Engine\Data\Subject;
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

  public function getSubjectKeysForAutomation(Automation $automation): array {
    $triggerData = array_values(array_filter(
      $automation->getSteps(),
      function (StepData $step): bool {
        return $step->getType() === StepData::TYPE_TRIGGER;
      }
    ));

    $triggers = array_filter(array_map(
      function (StepData $step): ?Trigger {
        return $this->registry->getTrigger($step->getKey());
      },
      $triggerData
    ));
    $all = [];
    foreach ($triggers as $trigger) {
      $all[] = $this->getSubjectKeysForTrigger($trigger);
    }
    $all = count($all) > 1 ? array_intersect(...$all) : $all[0] ?? [];
    return array_values(array_unique($all));
  }

  public function getSubjectKeysForTrigger(Trigger $trigger): array {
    $all = [];
    foreach ($trigger->getSubjectKeys() as $key) {
      $all = array_merge($all, $this->getSubjectKeysForSingleKey($key));
    }
    return $all;
  }

  /**
   * @param Subject[] $subjects
   * @return Subject[]
   */
  public function getAllSubjects(array $subjects): array {
    $transformerMap = $this->getTransformerMap();
    $all = [];
    foreach ($subjects as $subject) {
      $all[$subject->getKey()] = $subject;
    }

    $queue = array_map(
      function(Subject $subject): string {
        return $subject->getKey();
      },
      $subjects
    );
    while ($key = array_shift($queue)) {
      foreach ($transformerMap[$key] ?? [] as $transformer) {
          $newKey = $transformer->returns();
        if (!isset($all[$newKey])) {
          $all[$newKey] = $transformer->transform($all[$key]);
          $queue[] = $newKey;
        }
      }
    }
    return array_values($all);
  }

  private function getTransformerMap(): array {
    $transformerMap = [];
    foreach ($this->registry->getSubjectTransformer() as $transformer) {
      $transformerMap[$transformer->accepts()] = array_merge($transformerMap[$transformer->accepts()] ?? [], [$transformer]);
    }
    return $transformerMap;
  }

  /**
   * @return string[]
   */
  private function getSubjectKeysForSingleKey(string $subjectKey): array {
    $transformerMap = $this->getTransformerMap();
    $all = [$subjectKey];
    $queue = [$subjectKey];
    while ($key = array_shift($queue)) {
      foreach ($transformerMap[$key] ?? [] as $transformer) {
        $newKey = $transformer->returns();
        if (!in_array($newKey, $all, true)) {
          $all[] = $newKey;
          $queue[] = $newKey;
        }
      }
    }
    return $all;
  }
}

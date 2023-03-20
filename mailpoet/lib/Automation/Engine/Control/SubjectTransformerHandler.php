<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Control;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\Step as StepData;
use MailPoet\Automation\Engine\Data\Subject as SubjectData;
use MailPoet\Automation\Engine\Integration\Step;
use MailPoet\Automation\Engine\Integration\SubjectTransformer;
use MailPoet\Automation\Engine\Integration\Trigger;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Storage\AutomationStorage;

class SubjectTransformerHandler {

  /* @var Registry */
  private $registry;

  /* @var AutomationStorage */
  private $automationStorage;

  public function __construct(
    Registry $registry,
    AutomationStorage $automationStorage
  ) {
    $this->registry = $registry;
    $this->automationStorage = $automationStorage;
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
   * @return SubjectData[]|null
   */
  public function transformSubjectData(string $target, AutomationRun $automationRun): ?array {
    $automation = $this->automationStorage->getAutomation($automationRun->getAutomationId(), $automationRun->getVersionId());
    if (!$automation || !in_array($target, $this->subjectKeysForAutomation($automation), true)) {
      return null;
    }

    $transformedSubjects = [];
    $subjects = $automationRun->getSubjects();
    foreach ($subjects as $subject) {
      $transformerChain = $this->getTransformerChain($subject->getKey(), $target);
      if (!$transformerChain) {
        continue;
      }
      foreach ($transformerChain as $transformer) {
        $subject = $transformer->transform($subject);
      }
      $transformedSubjects[] = $subject;
    }
    return count($transformedSubjects) > 0 ? $transformedSubjects : null;
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

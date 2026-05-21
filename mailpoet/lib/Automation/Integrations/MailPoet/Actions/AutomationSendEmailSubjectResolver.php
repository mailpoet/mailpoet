<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Actions;

use MailPoet\Automation\Engine\Control\SubjectTransformerHandler;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;

class AutomationSendEmailSubjectResolver {
  private Registry $registry;
  private SubjectTransformerHandler $subjectTransformerHandler;

  public function __construct(
    Registry $registry,
    SubjectTransformerHandler $subjectTransformerHandler
  ) {
    $this->registry = $registry;
    $this->subjectTransformerHandler = $subjectTransformerHandler;
  }

  /** @return string[] */
  public function getGuaranteedSubjectKeysForStep(Automation $automation, Step $sendEmailStep): array {
    if ($sendEmailStep->getKey() !== SendEmailAction::KEY) {
      return [];
    }

    $triggerSteps = $this->getTriggerStepsReachingStep($automation, $sendEmailStep);
    if ($triggerSteps === []) {
      return $this->subjectTransformerHandler->getSubjectKeysForAutomation($automation);
    }

    return $this->intersectTriggerSubjectKeys($triggerSteps);
  }

  /** @return string[] */
  public function getGuaranteedSubjectKeysForEmail(Automation $automation, NewsletterEntity $newsletter): array {
    $exactStep = $this->getExactSendEmailStep($automation, $newsletter);
    if ($exactStep) {
      return $this->getGuaranteedSubjectKeysForStep($automation, $exactStep);
    }

    $steps = $this->getSendEmailStepsReferencingNewsletter($automation, $newsletter);
    if ($steps === []) {
      return [];
    }

    $subjectKeySets = array_map(function (Step $step) use ($automation): array {
      return $this->getGuaranteedSubjectKeysForStep($automation, $step);
    }, $steps);

    return $this->intersectSubjectKeySets($subjectKeySets);
  }

  public function hasGuaranteedSubjectForEmail(Automation $automation, NewsletterEntity $newsletter, string $subjectKey): bool {
    return in_array($subjectKey, $this->getGuaranteedSubjectKeysForEmail($automation, $newsletter), true);
  }

  /** @return Step[] */
  private function getTriggerStepsReachingStep(Automation $automation, Step $targetStep): array {
    $triggerSteps = [];
    foreach ($automation->getTriggers() as $triggerStep) {
      if ($this->stepCanReachStep($automation, $triggerStep, $targetStep)) {
        $triggerSteps[] = $triggerStep;
      }
    }
    return $triggerSteps;
  }

  private function stepCanReachStep(Automation $automation, Step $fromStep, Step $toStep): bool {
    $steps = $automation->getSteps();
    $stack = [$fromStep->getId()];
    $visited = [];

    while ($stack !== []) {
      $stepId = array_pop($stack);
      if (!is_string($stepId) || isset($visited[$stepId])) {
        continue;
      }
      $visited[$stepId] = true;

      if ($stepId === $toStep->getId()) {
        return true;
      }

      $step = $steps[$stepId] ?? null;
      if (!$step instanceof Step) {
        continue;
      }

      foreach ($step->getNextStepIds() as $nextStepId) {
        $stack[] = $nextStepId;
      }
    }

    return false;
  }

  /** @param Step[] $triggerSteps */
  private function intersectTriggerSubjectKeys(array $triggerSteps): array {
    $subjectKeySets = [];
    foreach ($triggerSteps as $triggerStep) {
      $trigger = $this->registry->getTrigger($triggerStep->getKey());
      if (!$trigger) {
        return [];
      }
      $subjectKeySets[] = $this->subjectTransformerHandler->getSubjectKeysForTrigger($trigger);
    }

    return $this->intersectSubjectKeySets($subjectKeySets);
  }

  /** @param array<int, string[]> $subjectKeySets */
  private function intersectSubjectKeySets(array $subjectKeySets): array {
    if ($subjectKeySets === []) {
      return [];
    }

    $subjectKeys = array_shift($subjectKeySets);
    foreach ($subjectKeySets as $nextSubjectKeys) {
      $subjectKeys = array_values(array_intersect($subjectKeys, $nextSubjectKeys));
    }
    $subjectKeys = array_values(array_unique($subjectKeys));
    sort($subjectKeys);
    return $subjectKeys;
  }

  private function getExactSendEmailStep(Automation $automation, NewsletterEntity $newsletter): ?Step {
    $stepId = $newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_AUTOMATION_STEP_ID);
    if (!is_string($stepId) || $stepId === '') {
      return null;
    }

    $step = $automation->getStep($stepId);
    return $step instanceof Step && $step->getKey() === SendEmailAction::KEY ? $step : null;
  }

  /** @return Step[] */
  private function getSendEmailStepsReferencingNewsletter(Automation $automation, NewsletterEntity $newsletter): array {
    $newsletterId = $newsletter->getId();
    $wpPostId = $newsletter->getWpPostId();

    return array_values(array_filter($automation->getSteps(), function (Step $step) use ($newsletterId, $wpPostId): bool {
      if ($step->getKey() !== SendEmailAction::KEY) {
        return false;
      }

      $args = $step->getArgs();
      $emailId = $args['email_id'] ?? null;
      $emailWpPostId = $args['email_wp_post_id'] ?? null;
      return ($newsletterId !== null && (int)$emailId === (int)$newsletterId)
        || ($wpPostId !== null && (int)$emailWpPostId === (int)$wpPostId);
    }));
  }
}

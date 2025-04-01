<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Shortcodes\Categories;

use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Integration\Subject;
use MailPoet\Automation\Engine\Integration\Trigger;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;

/**
 * Class Automation
 * @package MailPoet\Newsletter\Shortcodes\Categories
 *
 * This class implements shortcodes using the Subject(s) of an automation trigger.
 */
class Automation implements CategoryInterface {
  private AutomationStorage $automationStorage;
  private AutomationRunStorage $automationRunStorage;
  private Registry $registry;
  private ?AutomationRun $automationRun;

  public function __construct(
    AutomationStorage $automationStorage,
    AutomationRunStorage $automationRunStorage,
    Registry $registry
  ) {
    $this->automationStorage = $automationStorage;
    $this->automationRunStorage = $automationRunStorage;
    $this->registry = $registry;
  }

  public function process(
    array $shortcodeDetails,
    ?NewsletterEntity $newsletter = null,
    ?SubscriberEntity $subscriber = null,
    ?SendingQueueEntity $queue = null,
    string $content = '',
    bool $wpUserPreview = false
  ): ?string {
    if (!$newsletter) {
      return null;
    }

    // Get automation ID from newsletter options
    $automationOption = $newsletter->getOption('automationId');
    if (!$automationOption) {
      return null;
    }
    $automationId = (int)$automationOption->getValue();

    // Get available subjects for this automation
    $subjects = $this->getAvailableSubjects($automationId);

    // Process each subject's fields
    foreach ($subjects as $subject) {
      $fields = $subject->getFields();
      foreach ($fields as $field) {
        if ($field->getKey() !== $shortcodeDetails['action']) {
          continue;
        }

        return $this->renderSubjectField($subject, $field, $queue);
      }
    }

    return null;
  }

  private function renderSubjectField(Subject $subject, Field $field, ?SendingQueueEntity $queue): ?string {
    // Get automation run
    $run = $this->getAutomationRunFromQueue($queue);
    if (!$run) {
      return null;
    }

    // Get matching subject from AutomationRun
    $subjects = $run->getSubjects($subject->getKey());
    if (empty($subjects)) {
      return null;
    }

    // Get payload from subject data
    $subjectData = $subjects[0];
    $payload = $subject->getPayload($subjectData);

    // Get field value from payload, and try to render it depending on the its type.
    $value = $field->getValue($payload);
    if ($value === null) {
      return null;
    }
    switch ($field->getType()) {
      case Field::TYPE_STRING:
        return is_string($value) ? $value : null;
      case Field::TYPE_INTEGER:
        return is_numeric($value) ? (string)$value : null;
      case Field::TYPE_NUMBER:
        return is_numeric($value) ? (string)$value : null;
      case Field::TYPE_BOOLEAN:
        return is_bool($value) ? ($value ? '1' : '0') : null;
      case Field::TYPE_ENUM:
        return is_string($value) ? $value : null;
      case Field::TYPE_ENUM_ARRAY:
        return is_array($value) ? implode(', ', array_map('strval', $value)) : null;
      case Field::TYPE_DATETIME:
        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : null;
      default:
        return null;
    }
  }

  /**
   * Get the AutomationRun from the SendingQueueEntity metadata.
   *
   * @param SendingQueueEntity|null $queue
   * @return AutomationRun|null
   */
  private function getAutomationRunFromQueue(?SendingQueueEntity $queue): ?AutomationRun {
    if (isset($this->automationRun)) {
      return $this->automationRun;
    }

    if (!$queue) {
      return null;
    }

    // Get automation run ID from queue metadata
    $runId = $queue->getMeta()['automationRunId'] ?? null;
    if (!$runId) {
      return null;
    }

    $this->automationRun = $this->automationRunStorage->getAutomationRun($runId);

    return $this->automationRun;
  }

  /**
   * Get available subjects for an automation.
   *
   * @param int $automationId
   * @return array<Subject>
   */
  private function getAvailableSubjects(int $automationId): array {
    $automation = $this->automationStorage->getAutomation($automationId);
    if (!$automation) {
      return [];
    }

    // Get triggers from automation
    $triggers = array_map(
      function(?Step $triggerStep) {
        return $triggerStep ? $this->registry->getTrigger($triggerStep->getKey()) : null;
      },
      $automation->getTriggers()
    );

    // Get subject keys from each trigger.
    $subjectKeys = array_map(
      function(?Trigger $trigger) {
        return $trigger ? $trigger->getSubjectKeys() : [];
      },
      $triggers
    );

    // Return the available subjects.
    $subjectKeys = array_merge(...$subjectKeys);
    return array_filter(
      array_map(
        fn(string $subjectKey): ?Subject => $this->registry->getSubject($subjectKey),
        $subjectKeys
      )
    );
  }
}

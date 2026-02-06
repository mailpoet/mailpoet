<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\Core\Triggers;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Control\ActionScheduler;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\StepValidationArgs;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Integration\Trigger;
use MailPoet\Automation\Engine\Integration\ValidationException;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Engine\WordPress;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Segments\SegmentSubscribersRepository;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;

class ScheduledDateTimeTrigger implements Trigger {
  const KEY = 'core:scheduled-date-time';
  const SCHEDULED_HOOK = 'mailpoet/automation/triggers/scheduled-date-time';
  const BATCH_SIZE = 100;

  /** @var WordPress */
  private $wp;

  /** @var ActionScheduler */
  private $actionScheduler;

  /** @var AutomationStorage */
  private $automationStorage;

  /** @var SegmentSubscribersRepository */
  private $segmentSubscribersRepository;

  /** @var int|null */
  private $currentAutomationId;

  public function __construct(
    WordPress $wp,
    ActionScheduler $actionScheduler,
    AutomationStorage $automationStorage,
    SegmentSubscribersRepository $segmentSubscribersRepository
  ) {
    $this->wp = $wp;
    $this->actionScheduler = $actionScheduler;
    $this->automationStorage = $automationStorage;
    $this->segmentSubscribersRepository = $segmentSubscribersRepository;
  }

  public function getKey(): string {
    return self::KEY;
  }

  public function getName(): string {
    // translators: automation trigger title
    return __('Scheduled date/time', 'mailpoet');
  }

  public function getArgsSchema(): ObjectSchema {
    return Builder::object([
      'scheduled_at' => Builder::string()->required()->formatDateTime(),
      'segment_ids' => Builder::array(Builder::integer())->required()->minItems(1),
    ]);
  }

  public function getSubjectKeys(): array {
    return [
      SubscriberSubject::KEY,
      SegmentSubject::KEY,
    ];
  }

  public function registerHooks(): void {
    $this->wp->addAction(self::SCHEDULED_HOOK, [$this, 'handle'], 10, 2);
  }

  public function handle(int $automationId, int $batchOffset): void {
    $automation = $this->automationStorage->getAutomation($automationId);
    if (!$automation || $automation->getStatus() !== Automation::STATUS_ACTIVE) {
      return;
    }

    $this->currentAutomationId = $automationId;

    $trigger = $automation->getTrigger(self::KEY);
    if (!$trigger) {
      return;
    }

    $segmentIds = $trigger->getArgs()['segment_ids'] ?? [];
    if (!is_array($segmentIds) || empty($segmentIds)) {
      return;
    }

    $allSubscriberIds = $this->getSubscriberIdsInSegments($segmentIds);
    $batch = array_slice($allSubscriberIds, $batchOffset, self::BATCH_SIZE);

    $firstSegmentId = $segmentIds[0];
    foreach ($batch as $subscriberId) {
      $this->wp->doAction(Hooks::TRIGGER, $this, [
        new Subject(SubscriberSubject::KEY, ['subscriber_id' => $subscriberId]),
        new Subject(SegmentSubject::KEY, ['segment_id' => $firstSegmentId]),
      ]);
    }

    $nextOffset = $batchOffset + self::BATCH_SIZE;
    if ($nextOffset < count($allSubscriberIds)) {
      $this->actionScheduler->enqueue(self::SCHEDULED_HOOK, [$automationId, $nextOffset]);
    }

    $this->currentAutomationId = null;
  }

  public function isTriggeredBy(StepRunArgs $args): bool {
    return $args->getAutomation()->getId() === $this->currentAutomationId;
  }

  public function validate(StepValidationArgs $args): void {
    if (!$args->getAutomation()->needsFullValidation()) {
      return;
    }

    $scheduledAt = $args->getStep()->getArgs()['scheduled_at'] ?? null;
    if (!is_string($scheduledAt) || $scheduledAt === '') {
      throw ValidationException::create()
        ->withMessage(__('Scheduled date/time is required.', 'mailpoet'));
    }

    $scheduledDate = new DateTimeImmutable($scheduledAt);
    $now = new DateTimeImmutable();
    if ($scheduledDate <= $now) {
      throw ValidationException::create()
        ->withMessage(__('Scheduled date/time must be in the future.', 'mailpoet'));
    }
  }

  /**
   * @param int[] $segmentIds
   * @return int[]
   */
  private function getSubscriberIdsInSegments(array $segmentIds): array {
    $subscriberIds = [];
    foreach ($segmentIds as $segmentId) {
      $ids = $this->segmentSubscribersRepository->getSubscriberIdsInSegment($segmentId);
      foreach ($ids as $id) {
        $subscriberIds[$id] = $id;
      }
    }
    return array_values($subscriberIds);
  }
}

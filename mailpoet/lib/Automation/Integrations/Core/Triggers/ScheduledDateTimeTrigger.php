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
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Segments\SegmentSubscribersRepository;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;

/**
 * Time-based trigger that fires an automation at a user-specified date/time.
 *
 * Unlike event-based triggers (e.g. SomeoneSubscribesTrigger) which react to WordPress hooks,
 * this trigger uses Action Scheduler to fire at a scheduled timestamp. The flow is:
 *
 * 1. User configures a date/time and target segments in the automation editor
 * 2. On activation, ScheduledDateTimeHooks schedules an AS job at the specified timestamp
 * 3. At the scheduled time, AS fires our hook → handle() runs
 * 4. handle() queries subscribers in the configured segments and fires Hooks::TRIGGER
 *    for each subscriber, which TriggerHandler picks up to create AutomationRuns
 * 5. Subscribers are processed in batches of 100 to prevent timeouts with large segments
 *
 * This is the first of a planned series of time-based triggers. Future triggers
 * (birthday, booking date) will follow a similar AS-based pattern but with different
 * scheduling mechanisms (e.g. recurring for birthday).
 */
class ScheduledDateTimeTrigger implements Trigger {
  const KEY = 'core:scheduled-date-time';

  /** Action Scheduler hook name — AS calls this hook at the scheduled time */
  const SCHEDULED_HOOK = 'mailpoet/automation/triggers/scheduled-date-time';

  /** Number of subscribers to process per AS execution to prevent timeouts */
  const BATCH_SIZE = 100;

  /** @var WordPress */
  private $wp;

  /** @var ActionScheduler */
  private $actionScheduler;

  /** @var AutomationStorage */
  private $automationStorage;

  /** @var SegmentSubscribersRepository */
  private $segmentSubscribersRepository;

  /**
   * Tracks which automation is currently being processed inside handle().
   * Used by isTriggeredBy() to ensure only the correct automation creates runs.
   * Set at the start of handle(), cleared at the end. Null when not processing.
   * @var int|null
   */
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
    ];
  }

  /** Register the AS hook so handle() is called when the scheduled time arrives. */
  public function registerHooks(): void {
    $this->wp->addAction(self::SCHEDULED_HOOK, [$this, 'handle'], 10, 2);
  }

  /**
   * Main execution — called by Action Scheduler at the scheduled time.
   *
   * Uses cursor-based pagination: each batch fetches subscribers with ID > $lastProcessedId
   * (ORDER BY id ASC, LIMIT BATCH_SIZE). This avoids loading all subscriber IDs into memory
   * and produces stable pagination even if segment membership changes between batches.
   *
   * The automation status is checked at the start of each batch, so deactivating
   * an automation mid-processing will stop subsequent batches from running.
   *
   * @param int $automationId     The automation to process
   * @param int $lastProcessedId  Cursor: the last subscriber ID processed (0 for first batch)
   */
  public function handle(int $automationId, int $lastProcessedId): void {
    $automation = $this->automationStorage->getAutomation($automationId);
    if (!$automation || $automation->getStatus() !== Automation::STATUS_ACTIVE) {
      return;
    }

    $trigger = $automation->getTrigger(self::KEY);
    if (!$trigger) {
      return;
    }

    $segmentIds = $trigger->getArgs()['segment_ids'] ?? [];
    if (!is_array($segmentIds) || empty($segmentIds)) {
      return;
    }

    $this->currentAutomationId = $automationId;
    try {
      $batch = $this->getSubscriberIdsBatch($segmentIds, $lastProcessedId);

      foreach ($batch as $subscriberId) {
        $this->wp->doAction(Hooks::TRIGGER, $this, [
          new Subject(SubscriberSubject::KEY, ['subscriber_id' => $subscriberId]),
        ]);
      }

      // If a full batch was returned, there may be more — schedule next batch with cursor
      if (count($batch) >= self::BATCH_SIZE) {
        $nextCursor = end($batch);
        $this->actionScheduler->enqueue(self::SCHEDULED_HOOK, [$automationId, $nextCursor]);
      }
    } finally {
      $this->currentAutomationId = null;
    }
  }

  /**
   * Called by TriggerHandler for each automation that uses this trigger key.
   * Returns true only for the automation currently being processed in handle(),
   * preventing other automations with the same trigger from creating runs.
   */
  public function isTriggeredBy(StepRunArgs $args): bool {
    return $args->getAutomation()->getId() === $this->currentAutomationId;
  }

  /**
   * Validate trigger args when activating an automation.
   * Only enforced for active automations (needsFullValidation), allowing
   * drafts to be saved freely with incomplete or past dates.
   */
  public function validate(StepValidationArgs $args): void {
    if (!$args->getAutomation()->needsFullValidation()) {
      return;
    }

    $scheduledAt = $args->getStep()->getArgs()['scheduled_at'] ?? null;
    if (!is_string($scheduledAt) || $scheduledAt === '') {
      throw ValidationException::create()
        ->withMessage(__('Scheduled date/time is required.', 'mailpoet'));
    }

    try {
      $scheduledDate = new DateTimeImmutable($scheduledAt);
    } catch (\Exception $e) {
      throw ValidationException::create()
        ->withMessage(__('Invalid date/time format.', 'mailpoet'));
    }
    $now = new DateTimeImmutable();
    if ($scheduledDate <= $now) {
      throw ValidationException::create()
        ->withMessage(__('Scheduled date/time must be in the future.', 'mailpoet'));
    }
  }

  /**
   * Fetch one batch of unique subscriber IDs across configured segments using cursor-based pagination.
   * Only loads BATCH_SIZE IDs into memory per call (via SQL LIMIT), avoiding the need to load all
   * subscriber IDs at once. The cursor ($lastProcessedId) ensures stable pagination even if segment
   * membership changes between batches.
   *
   * @param int[] $segmentIds
   * @param int   $lastProcessedId Cursor: fetch subscribers with ID > this value (0 for first batch)
   * @return int[]
   */
  private function getSubscriberIdsBatch(array $segmentIds, int $lastProcessedId): array {
    return $this->segmentSubscribersRepository->getSubscriberIdsInSegments(
      $segmentIds,
      $lastProcessedId,
      self::BATCH_SIZE
    );
  }
}

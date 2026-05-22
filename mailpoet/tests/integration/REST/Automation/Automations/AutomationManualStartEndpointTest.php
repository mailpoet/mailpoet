<?php declare(strict_types = 1);

namespace MailPoet\REST\Automation\Automations;

require_once __DIR__ . '/../AutomationTest.php';

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\ManualStart\ManualStartAudienceRepository;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SomeoneSubscribesTrigger;
use MailPoet\Cron\Workers\Automations\ManualAutomationStartWorker;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\REST\Automation\AutomationTest;
use MailPoet\Test\DataFactories\Automation as AutomationFactory;
use MailPoet\Test\DataFactories\AutomationRun;
use MailPoet\Test\DataFactories\DynamicSegment;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class AutomationManualStartEndpointTest extends AutomationTest {
  /** @var EntityManager */
  private $em;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var ManualStartAudienceRepository */
  private $audienceRepository;

  public function _before() {
    parent::_before();
    $this->em = $this->diContainer->get(EntityManager::class);
    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->audienceRepository = $this->diContainer->get(ManualStartAudienceRepository::class);
  }

  public function testPreviewReturnsCountsAndStartQueuesEligibleSubscribers(): void {
    $segment = (new Segment())->create();
    $eligible = (new Subscriber())->withSegments([$segment])->create();
    $alreadyEntered = (new Subscriber())->withSegments([$segment])->create();
    (new Subscriber())->withSegments([$segment])->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)->create();
    (new Subscriber())->withSegments([$segment])->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)->create();
    (new Subscriber())->withSegments([$segment])->withStatus(SubscriberEntity::STATUS_BOUNCED)->create();
    (new Subscriber())->withSegments([$segment])->withStatus(SubscriberEntity::STATUS_INACTIVE)->create();
    (new Subscriber())->withSegments([$segment])->withDeletedAt(new \DateTimeImmutable())->create();
    (new Subscriber())->create();

    $automation = (new AutomationFactory())
      ->withStatusActive()
      ->withSomeoneSubscribesTrigger()
      ->create();
    (new AutomationRun())
      ->withAutomation($automation)
      ->withTriggerKey(SomeoneSubscribesTrigger::KEY)
      ->withSubject(new Subject(SubscriberSubject::KEY, ['subscriber_id' => $alreadyEntered->getId()]))
      ->withSubject(new Subject(SegmentSubject::KEY, ['segment_id' => $segment->getId()]))
      ->create();

    $listing = $this->get('/mailpoet/v1/automations')['data'];
    $item = $this->findAutomationListItem($listing['items'], $automation->getId());
    $manualStart = $item['manual_start'] ?? null;
    $this->assertIsArray($manualStart);
    $this->assertSame([
      'supported' => true,
      'trigger_key' => SomeoneSubscribesTrigger::KEY,
      'segment_ids' => null,
    ], $manualStart);

    $preview = $this->postPreview($automation->getId(), $segment->getId())['data'];
    $this->assertSame($automation->getId(), $preview['automation_id']);
    $this->assertSame($segment->getId(), $preview['segment_id']);
    $this->assertNull($preview['filter_segment_id']);
    $this->assertSame(7, $preview['selected_count']);
    $this->assertSame(1, $preview['eligible_count']);
    $this->assertSame(1, $preview['skipped_by_reason']['already_entered']);
    $this->assertSame(1, $preview['skipped_by_reason']['unconfirmed']);
    $this->assertSame(1, $preview['skipped_by_reason']['unsubscribed']);
    $this->assertSame(1, $preview['skipped_by_reason']['bounced']);
    $this->assertSame(1, $preview['skipped_by_reason']['not_subscribed']);
    $this->assertSame(1, $preview['skipped_by_reason']['deleted']);
    $this->assertContains('trigger_filter_mismatch', $preview['deferred_reason_keys']);
    $this->assertFalse($preview['duplicate_in_progress']);

    $start = $this->postStart($automation->getId(), $segment->getId(), $preview['preview_signature'])['data'];
    $this->assertSame($automation->getId(), $start['automation_id']);
    $this->assertSame($segment->getId(), $start['segment_id']);
    $this->assertSame(7, $start['selected_count']);
    $this->assertSame(1, $start['eligible_count']);
    $this->assertSame(1, $start['queued_count']);
    $this->assertGreaterThan(0, $start['task_id']);

    $task = $this->scheduledTasksRepository->findOneById($start['task_id']);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $this->assertSame(ManualAutomationStartWorker::TASK_TYPE, $task->getType());
    $this->assertSame(ScheduledTaskEntity::STATUS_SCHEDULED, $task->getStatus());
    $taskMeta = $task->getMeta() ?? [];
    $this->assertSame($automation->getId(), $this->getInt($taskMeta['automation_id'] ?? null));
    $this->assertSame($automation->getVersionId(), $this->getInt($taskMeta['automation_version_id'] ?? null));
    $this->assertSame(1, $this->countTaskSubscribers((int)$task->getId()));
    $this->assertTrue($this->taskHasSubscriber((int)$task->getId(), (int)$eligible->getId()));

    $duplicate = $this->postPreview($automation->getId(), $segment->getId());
    $this->assertSame('manual_start_in_progress', $duplicate['code']);
    $this->assertSame(409, $duplicate['data']['status']);
  }

  public function testStartRejectsStalePreviewSignatureWithRefreshedPreview(): void {
    $segment = (new Segment())->create();
    (new Subscriber())->withSegments([$segment])->create();
    $automation = (new AutomationFactory())
      ->withStatusActive()
      ->withSomeoneSubscribesTrigger()
      ->create();

    $preview = $this->postPreview($automation->getId(), $segment->getId())['data'];
    (new Subscriber())->withSegments([$segment])->create();

    $response = $this->postStart($automation->getId(), $segment->getId(), $preview['preview_signature']);
    $this->assertSame('manual_start_stale_preview', $response['code']);
    $this->assertSame(409, $response['data']['status']);
    $this->assertSame(2, $response['data']['preview']['eligible_count']);
    $this->assertNotSame($preview['preview_signature'], $response['data']['preview']['preview_signature']);
  }

  public function testStartReportsStalePreviewBeforeZeroEligibleAudience(): void {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())->withSegments([$segment])->create();
    $automation = (new AutomationFactory())
      ->withStatusActive()
      ->withSomeoneSubscribesTrigger()
      ->create();

    $preview = $this->postPreview($automation->getId(), $segment->getId())['data'];
    $subscriber->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);
    $this->em->flush();

    $response = $this->postStart($automation->getId(), $segment->getId(), $preview['preview_signature']);
    $this->assertSame('manual_start_stale_preview', $response['code']);
    $this->assertSame(409, $response['data']['status']);
    $this->assertSame(0, $response['data']['preview']['eligible_count']);
  }

  public function testPreviewAndStartAcceptExplicitNullFilterSegmentId(): void {
    $segment = (new Segment())->create();
    (new Subscriber())->withSegments([$segment])->create();
    $automation = (new AutomationFactory())
      ->withStatusActive()
      ->withSomeoneSubscribesTrigger()
      ->create();

    $preview = $this->postPreview($automation->getId(), $segment->getId(), null, true)['data'];
    $this->assertNull($preview['filter_segment_id']);
    $this->assertSame(1, $preview['eligible_count']);

    $start = $this->postStart($automation->getId(), $segment->getId(), $preview['preview_signature'], null, true)['data'];
    $this->assertNull($start['filter_segment_id']);
    $this->assertSame(1, $start['queued_count']);
  }

  public function testDynamicFilterNarrowsEligibleSubscribers(): void {
    $segment = (new Segment())->create();
    $filterSegment = (new DynamicSegment())->withEngagementScoreFilter(40, 'higherThan')->create();
    $eligible = (new Subscriber())->withSegments([$segment])->withEngagementScore(50)->create();
    (new Subscriber())->withSegments([$segment])->withEngagementScore(10)->create();
    $automation = (new AutomationFactory())
      ->withStatusActive()
      ->withSomeoneSubscribesTrigger()
      ->create();

    $preview = $this->postPreview($automation->getId(), $segment->getId(), $filterSegment->getId())['data'];
    $this->assertSame($filterSegment->getId(), $preview['filter_segment_id']);
    $this->assertSame(2, $preview['selected_count']);
    $this->assertSame(1, $preview['eligible_count']);
    $this->assertSame(1, $preview['skipped_by_reason']['dynamic_filter_mismatch']);

    $start = $this->postStart($automation->getId(), $segment->getId(), $preview['preview_signature'], $filterSegment->getId())['data'];
    $this->assertSame(1, $start['queued_count']);
    $this->assertTrue($this->taskHasSubscriber($start['task_id'], (int)$eligible->getId()));
  }

  public function testPreviewValidatesSupportedAutomationAndSegments(): void {
    $defaultSegment = (new Segment())->create();
    $dynamicSegment = (new Segment())->withType(SegmentEntity::TYPE_DYNAMIC)->create();
    $automation = (new AutomationFactory())
      ->withStatusActive()
      ->withSomeoneSubscribesTrigger()
      ->create();

    $response = $this->postPreview($automation->getId(), $dynamicSegment->getId());
    $this->assertSame('manual_start_invalid_segment', $response['code']);
    $this->assertSame(400, $response['data']['status']);

    $unsupportedAutomation = (new AutomationFactory())
      ->withStatusActive()
      ->withStep(new Step('trigger', Step::TYPE_TRIGGER, 'mailpoet:someone-unsubscribes', [], []))
      ->create();
    $response = $this->postPreview($unsupportedAutomation->getId(), $defaultSegment->getId());
    $this->assertSame('manual_start_unsupported_trigger', $response['code']);
    $this->assertSame(400, $response['data']['status']);
  }

  public function testPreviewRejectsListsNotAllowedByTriggerSettings(): void {
    $allowedSegment = (new Segment())->create();
    $blockedSegment = (new Segment())->create();
    $automation = (new AutomationFactory())
      ->withStatusActive()
      ->withStep(new Step(
        'trigger',
        Step::TYPE_TRIGGER,
        SomeoneSubscribesTrigger::KEY,
        ['segment_ids' => [$allowedSegment->getId()]],
        []
      ))
      ->create();

    $listing = $this->get('/mailpoet/v1/automations')['data'];
    $item = $this->findAutomationListItem($listing['items'], $automation->getId());
    $manualStart = $item['manual_start'] ?? null;
    $this->assertIsArray($manualStart);
    $this->assertSame([$allowedSegment->getId()], $manualStart['segment_ids']);

    $response = $this->postPreview($automation->getId(), $blockedSegment->getId());
    $this->assertSame('manual_start_segment_not_allowed', $response['code']);
    $this->assertSame(400, $response['data']['status']);
  }

  public function testManualStartActiveTaskBlocksOnlyInProgressTasks(): void {
    $segment = (new Segment())->create();
    $otherSegment = (new Segment())->create();
    (new Subscriber())->withSegments([$segment])->create();
    (new Subscriber())->withSegments([$otherSegment])->create();
    $automation = (new AutomationFactory())
      ->withStatusActive()
      ->withSomeoneSubscribesTrigger()
      ->create();

    $preview = $this->postPreview($automation->getId(), $segment->getId())['data'];
    $start = $this->postStart($automation->getId(), $segment->getId(), $preview['preview_signature'])['data'];
    $task = $this->scheduledTasksRepository->findOneById($start['task_id']);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);

    $duplicateStart = $this->postStart($automation->getId(), $segment->getId(), $preview['preview_signature']);
    $this->assertSame('manual_start_in_progress', $duplicateStart['code']);
    $this->assertSame(409, $duplicateStart['data']['status']);

    $otherListPreview = $this->postPreview($automation->getId(), $otherSegment->getId());
    $this->assertSame('manual_start_in_progress', $otherListPreview['code']);

    $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $this->em->flush();
    $allowedAfterCompleted = $this->postPreview($automation->getId(), $segment->getId());
    $this->assertArrayHasKey('data', $allowedAfterCompleted);

    $task->setStatus('failed');
    $this->em->flush();
    $allowedAfterFailed = $this->postPreview($automation->getId(), $segment->getId());
    $this->assertArrayHasKey('data', $allowedAfterFailed);

    $task->setStatus(ScheduledTaskEntity::VIRTUAL_STATUS_RUNNING);
    $this->em->flush();
    $blockedWhileRunning = $this->postPreview($automation->getId(), $segment->getId());
    $this->assertSame('manual_start_in_progress', $blockedWhileRunning['code']);
  }

  public function testPreviewAndStartRejectGuests(): void {
    $segment = (new Segment())->create();
    $automation = (new AutomationFactory())
      ->withStatusActive()
      ->withSomeoneSubscribesTrigger()
      ->create();

    wp_set_current_user(0);

    $preview = $this->postPreview($automation->getId(), $segment->getId());
    $this->assertSame('rest_forbidden', $preview['code']);
    $this->assertSame(401, $preview['data']['status']);

    $start = $this->postStart($automation->getId(), $segment->getId(), 'signature');
    $this->assertSame('rest_forbidden', $start['code']);
    $this->assertSame(401, $start['data']['status']);
  }

  public function testPreviewAndStartRejectUsersWithoutAutomationCapability(): void {
    $segment = (new Segment())->create();
    $automation = (new AutomationFactory())
      ->withStatusActive()
      ->withSomeoneSubscribesTrigger()
      ->create();
    $userId = wp_insert_user([
      'user_login' => 'manual-start-subscriber',
      'user_pass' => 'password',
      'user_email' => 'manual-start-subscriber@example.com',
      'role' => 'subscriber',
    ]);
    $this->assertIsNumeric($userId);

    try {
      wp_set_current_user((int)$userId);

      $preview = $this->postPreview($automation->getId(), $segment->getId());
      $this->assertSame('rest_forbidden', $preview['code']);
      $this->assertSame(403, $preview['data']['status']);

      $start = $this->postStart($automation->getId(), $segment->getId(), 'signature');
      $this->assertSame('rest_forbidden', $start['code']);
      $this->assertSame(403, $start['data']['status']);
    } finally {
      wp_set_current_user(1);
      is_multisite() ? wpmu_delete_user((int)$userId) : wp_delete_user((int)$userId);
    }
  }

  public function testStartQueuesSubscribersAcrossBoundedChunks(): void {
    $segment = (new Segment())->create();
    for ($i = 0; $i < ManualStartAudienceRepository::QUEUE_CHUNK_SIZE + 5; $i++) {
      (new Subscriber())->withSegments([$segment])->create();
    }
    $automation = (new AutomationFactory())
      ->withStatusActive()
      ->withSomeoneSubscribesTrigger()
      ->create();

    $preview = $this->postPreview($automation->getId(), $segment->getId())['data'];
    $start = $this->postStart($automation->getId(), $segment->getId(), $preview['preview_signature'])['data'];

    $this->assertSame(ManualStartAudienceRepository::QUEUE_CHUNK_SIZE + 5, $start['queued_count']);
    $this->assertSame(ManualStartAudienceRepository::QUEUE_CHUNK_SIZE + 5, $this->countTaskSubscribers($start['task_id']));
  }

  public function testQueueChunksContinueWhenInsertIgnoreSkipsExistingRows(): void {
    $segment = (new Segment())->create();
    $firstSubscriber = (new Subscriber())->withSegments([$segment])->create();
    for ($i = 1; $i < ManualStartAudienceRepository::QUEUE_CHUNK_SIZE + 5; $i++) {
      (new Subscriber())->withSegments([$segment])->create();
    }
    $automation = (new AutomationFactory())
      ->withStatusActive()
      ->withSomeoneSubscribesTrigger()
      ->create();
    $task = new ScheduledTaskEntity();
    $task->setType(ManualAutomationStartWorker::TASK_TYPE);
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $task->setScheduledAt(new \DateTimeImmutable());
    $this->em->persist($task);
    $this->em->flush();

    $this->em->persist(new ScheduledTaskSubscriberEntity($task, $firstSubscriber));
    $this->em->flush();

    $queuedCount = $this->audienceRepository->queueEligibleSubscribers(
      $task,
      $automation->getId(),
      $segment,
      null
    );

    $this->assertSame(ManualStartAudienceRepository::QUEUE_CHUNK_SIZE + 4, $queuedCount);
    $this->assertSame(ManualStartAudienceRepository::QUEUE_CHUNK_SIZE + 5, $this->countTaskSubscribers((int)$task->getId()));
  }

  private function postPreview(int $automationId, ?int $segmentId, ?int $filterSegmentId = null, bool $includeFilterSegmentId = false): array {
    if ($segmentId === null) {
      $this->fail('Segment ID is required for manual start preview requests.');
    }
    $payload = ['segment_id' => $segmentId];
    if ($filterSegmentId !== null || $includeFilterSegmentId) {
      $payload['filter_segment_id'] = $filterSegmentId;
    }
    return $this->post("/mailpoet/v1/automations/$automationId/manual-start/preview", ['json' => $payload]);
  }

  private function postStart(int $automationId, ?int $segmentId, string $previewSignature, ?int $filterSegmentId = null, bool $includeFilterSegmentId = false): array {
    if ($segmentId === null) {
      $this->fail('Segment ID is required for manual start requests.');
    }
    $payload = [
      'segment_id' => $segmentId,
      'preview_signature' => $previewSignature,
    ];
    if ($filterSegmentId !== null || $includeFilterSegmentId) {
      $payload['filter_segment_id'] = $filterSegmentId;
    }
    return $this->post("/mailpoet/v1/automations/$automationId/manual-start", ['json' => $payload]);
  }

  /**
   * @param array<int, array<string, mixed>> $items
   * @return array<string, mixed>
   */
  private function findAutomationListItem(array $items, int $automationId): array {
    foreach ($items as $item) {
      $id = $item['id'] ?? null;
      if (is_numeric($id) && (int)$id === $automationId) {
        return $item;
      }
    }
    $this->fail('Automation list item not found.');
  }

  private function countTaskSubscribers(int $taskId): int {
    $table = $this->em->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName();
    $count = $this->em->getConnection()->executeQuery(
      "SELECT COUNT(*) FROM $table WHERE task_id = :taskId",
      ['taskId' => $taskId]
    )->fetchOne();

    return $this->getInt($count);
  }

  private function taskHasSubscriber(int $taskId, int $subscriberId): bool {
    $table = $this->em->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName();
    $count = $this->em->getConnection()->executeQuery(
      "SELECT COUNT(*) FROM $table WHERE task_id = :taskId AND subscriber_id = :subscriberId",
      [
        'taskId' => $taskId,
        'subscriberId' => $subscriberId,
      ]
    )->fetchOne();

    return $this->getInt($count) === 1;
  }

  /** @param mixed $value */
  private function getInt($value): int {
    if (is_numeric($value)) {
      return (int)$value;
    }
    return 0;
  }
}

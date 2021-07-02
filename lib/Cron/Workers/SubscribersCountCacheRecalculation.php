<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Cache\TransientCache;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Models\ScheduledTask;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Segments\SegmentSubscribersRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class SubscribersCountCacheRecalculation extends SimpleWorker {
  private const EXPIRATION_IN_MINUTES = 30;
  const TASK_TYPE = 'subscribers_count_cache_recalculation';
  const AUTOMATIC_SCHEDULING = false;
  const SUPPORT_MULTIPLE_INSTANCES = false;

  /** @var TransientCache */
  private $transientCache;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SegmentSubscribersRepository */
  private $segmentSubscribersRepository;

  public function __construct(
    TransientCache $transientCache,
    SegmentsRepository $segmentsRepository,
    SegmentSubscribersRepository $segmentSubscribersRepository,
    WPFunctions $wp
  ) {
    parent::__construct($wp);
    $this->transientCache = $transientCache;
    $this->segmentsRepository = $segmentsRepository;
    $this->segmentSubscribersRepository = $segmentSubscribersRepository;
  }

  public function processTaskStrategy(ScheduledTask $task, $timer) {
    $segments = $this->segmentsRepository->findAll();
    foreach ($segments as $segment) {
      $this->recalculateSegmentCache($timer, (int)$segment->getId(), $segment);
    }

    // update cache for subscribers without segment
    $this->recalculateSegmentCache($timer, 0);

    return true;
  }

  private function recalculateSegmentCache($timer, int $segmentId, ?SegmentEntity $segment = null): void {
    $this->cronHelper->enforceExecutionLimit($timer);
    $now = Carbon::now();
    $item = $this->transientCache->getItem(TransientCache::SUBSCRIBERS_STATISTICS_COUNT_KEY, $segmentId);
    if ($item === null || !isset($item['created_at']) || $now->diffInMinutes($item['created_at']) > self::EXPIRATION_IN_MINUTES) {
      $this->transientCache->invalidateItem(TransientCache::SUBSCRIBERS_STATISTICS_COUNT_KEY, $segmentId);
      if ($segment) {
        $this->segmentSubscribersRepository->getSubscribersStatisticsCount($segment);
      } else {
        $this->segmentSubscribersRepository->getSubscribersWithoutSegmentStatisticsCount();
      }
    }
  }

  public function getNextRunDate() {
    return Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));
  }

  public function shouldBeScheduled(): bool {
    $now = Carbon::now();
    $oldestCreatedAt = $this->transientCache->getOldestCreatedAt(TransientCache::SUBSCRIBERS_STATISTICS_COUNT_KEY);
    return $oldestCreatedAt === null || $now->diffInMinutes($oldestCreatedAt) > self::EXPIRATION_IN_MINUTES;
  }
}

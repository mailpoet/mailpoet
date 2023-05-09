<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Cache\TransientCache;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\InvalidStateException;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Segments\SegmentSubscribersRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Tags\TagRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class SubscribersCountsController {
  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SegmentSubscribersRepository */
  private $segmentSubscribersRepository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var TagRepository */
  private $tagRepository;

  /** @var TransientCache */
  private $transientCache;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    SegmentsRepository $segmentsRepository,
    SegmentSubscribersRepository $segmentSubscribersRepository,
    SubscribersRepository $subscribersRepository,
    TagRepository $subscriberTagRepository,
    TransientCache $transientCache,
    WPFunctions $wp
  ) {

    $this->segmentSubscribersRepository = $segmentSubscribersRepository;
    $this->transientCache = $transientCache;
    $this->segmentsRepository = $segmentsRepository;
    $this->subscribersRepository = $subscribersRepository;
    $this->tagRepository = $subscriberTagRepository;
    $this->wp = $wp;
  }

  public function getSubscribersWithoutSegmentStatisticsCount(): array {
    $result = $this->transientCache->getItem(TransientCache::SUBSCRIBERS_STATISTICS_COUNT_KEY, 0)['item'] ?? null;
    if (!$result) {
      $result = $this->recalculateSubscribersWithoutSegmentStatisticsCache();
    }
    return $result;
  }

  public function getSegmentStatisticsCount(SegmentEntity $segment): array {
    $result = $this->transientCache->getItem(TransientCache::SUBSCRIBERS_STATISTICS_COUNT_KEY, (int)$segment->getId())['item'] ?? null;
    if (!$result) {
      $result = $this->recalculateSegmentStatisticsCache($segment);
    }
    return $result;
  }

  public function getSegmentGlobalStatusStatisticsCount(SegmentEntity $segment): array {
    $result = $this->transientCache->getItem(TransientCache::SUBSCRIBERS_GLOBAL_STATUS_STATISTICS_COUNT_KEY, (int)$segment->getId())['item'] ?? null;
    if (!$result) {
      $result = $this->recalculateSegmentGlobalStatusStatisticsCache($segment);
    }
    return $result;
  }

  public function getSegmentStatisticsCountById(int $segmentId): array {
    $result = $this->transientCache->getItem(TransientCache::SUBSCRIBERS_STATISTICS_COUNT_KEY, $segmentId)['item'] ?? null;
    if (!$result) {
      $segment = $this->segmentsRepository->findOneById($segmentId);
      if (!$segment) {
        throw new InvalidStateException();
      }
      $result = $this->recalculateSegmentStatisticsCache($segment);
    }
    return $result;
  }

  public function getHomepageStatistics(): array {
    $result = $this->transientCache->getItem(TransientCache::SUBSCRIBERS_HOMEPAGE_STATISTICS_COUNT_KEY, 0) ?? [];
    if (!$result) {
      $result = $this->recalculateHomepageStatisticsCache();
    }
    return $result;
  }

  public function recalculateSegmentGlobalStatusStatisticsCache(SegmentEntity $segment): array {
    $result = $this->segmentSubscribersRepository->getSubscribersGlobalStatusStatisticsCount($segment);
    $this->transientCache->setItem(
      TransientCache::SUBSCRIBERS_GLOBAL_STATUS_STATISTICS_COUNT_KEY,
      $result,
      (int)$segment->getId()
    );
    return $result;
  }

  public function recalculateSegmentStatisticsCache(SegmentEntity $segment): array {
    $result = $this->segmentSubscribersRepository->getSubscribersStatisticsCount($segment);
    $this->transientCache->setItem(
      TransientCache::SUBSCRIBERS_STATISTICS_COUNT_KEY,
      $result,
      (int)$segment->getId()
    );
    return $result;
  }

  public function recalculateSubscribersWithoutSegmentStatisticsCache(): array {
    $result = $this->segmentSubscribersRepository->getSubscribersWithoutSegmentStatisticsCount();
    $this->transientCache->setItem(TransientCache::SUBSCRIBERS_STATISTICS_COUNT_KEY, $result, 0);
    return $result;
  }

  public function recalculateHomepageStatisticsCache(): array {
    $thirtyDaysAgo = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'))->subDays(30);
    $result = [];
    $result['listsDataSubscribed'] = $this->subscribersRepository->getListLevelCountsOfSubscribedAfter($thirtyDaysAgo);
    $result['listsDataUnsubscribed'] = $this->subscribersRepository->getListLevelCountsOfUnsubscribedAfter($thirtyDaysAgo);
    $result['subscribedCount'] = $this->subscribersRepository->getCountOfLastSubscribedAfter($thirtyDaysAgo);
    $result['unsubscribedCount'] = $this->subscribersRepository->getCountOfUnsubscribedAfter($thirtyDaysAgo);
    $result['subscribedSubscribersCount'] = $this->subscribersRepository->getCountOfSubscribersForStates([SubscriberEntity::STATUS_SUBSCRIBED]);
    $this->transientCache->setItem(
      TransientCache::SUBSCRIBERS_HOMEPAGE_STATISTICS_COUNT_KEY,
      $result,
      0
    );
    return $result;
  }

  public function removeRedundancyFromStatisticsCache() {
    $segments = $this->segmentsRepository->findAll();
    $segmentIds = array_map(function (SegmentEntity $segment): int {
      return (int)$segment->getId();
    }, $segments);
    foreach ($this->transientCache->getItems(TransientCache::SUBSCRIBERS_STATISTICS_COUNT_KEY) as $id => $item) {
      if (!in_array($id, $segmentIds)) {
        $this->transientCache->invalidateItem(TransientCache::SUBSCRIBERS_STATISTICS_COUNT_KEY, $id);
      }
    }
    foreach ($this->transientCache->getItems(TransientCache::SUBSCRIBERS_GLOBAL_STATUS_STATISTICS_COUNT_KEY) as $id => $item) {
      if (!in_array($id, $segmentIds)) {
        $this->transientCache->invalidateItem(TransientCache::SUBSCRIBERS_GLOBAL_STATUS_STATISTICS_COUNT_KEY, $id);
      }
    }
  }

  /**
   * @return array<int, array{id: int, name: string, subscribersCount: int}>
   */
  public function getTagsStatisticsCount(?string $status, bool $isDeleted): array {
    return $this->tagRepository->getSubscriberStatisticsCount($status, $isDeleted);
  }
}

<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Cache\TransientCache;
use MailPoet\Entities\SegmentEntity;
use MailPoet\InvalidStateException;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Segments\SegmentSubscribersRepository;
use MailPoet\Tags\TagRepository;

class SubscribersCountsController {
  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SegmentSubscribersRepository */
  private $segmentSubscribersRepository;

  /** @var TagRepository */
  private $tagRepository;

  /** @var TransientCache */
  private $transientCache;

  public function __construct(
    SegmentsRepository $segmentsRepository,
    SegmentSubscribersRepository $segmentSubscribersRepository,
    TagRepository $subscriberTagRepository,
    TransientCache $transientCache
  ) {

    $this->segmentSubscribersRepository = $segmentSubscribersRepository;
    $this->transientCache = $transientCache;
    $this->segmentsRepository = $segmentsRepository;
    $this->tagRepository = $subscriberTagRepository;
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

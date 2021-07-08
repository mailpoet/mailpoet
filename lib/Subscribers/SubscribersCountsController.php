<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Cache\TransientCache;
use MailPoet\Entities\SegmentEntity;
use MailPoet\InvalidStateException;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Segments\SegmentSubscribersRepository;

class SubscribersCountsController {
  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SegmentSubscribersRepository */
  private $segmentSubscribersRepository;

  /** @var TransientCache */
  private $transientCache;

  public function __construct(
    SegmentsRepository $segmentsRepository,
    SegmentSubscribersRepository $segmentSubscribersRepository,
    TransientCache $transientCache
  ) {

    $this->segmentSubscribersRepository = $segmentSubscribersRepository;
    $this->transientCache = $transientCache;
    $this->segmentsRepository = $segmentsRepository;
  }

  public function getSubscribersWithoutSegmentStatisticsCount(): array {
    $result = $this->transientCache->getItem(TransientCache::SUBSCRIBERS_STATISTICS_COUNT_KEY, 0);
    if (!$result) {
      $result = $this->recalculateSubscribersWithoutSegmentStatisticsCache();
    }
    return $result;
  }

  public function getSegmentStatisticsCount(SegmentEntity $segment): array {
    $result = $this->transientCache->getItem(TransientCache::SUBSCRIBERS_STATISTICS_COUNT_KEY, (int)$segment->getId());
    if (!$result) {
      $result = $this->recalculateSegmentStatisticsCache($segment);
    }
    return $result;
  }

  public function getSegmentGlobalStatusStatisticsCount(SegmentEntity $segment): array {
    $result = $this->transientCache->getItem(TransientCache::SUBSCRIBERS_GLOBAL_STATUS_STATISTICS_COUNT_KEY, (int)$segment->getId());
    if (!$result) {
      $result = $this->recalculateSegmentGlobalStatusStatisticsCache($segment);
    }
    return $result;
  }

  public function getSegmentStatisticsCountById(int $segmentId): array {
    $result = $this->transientCache->getItem(TransientCache::SUBSCRIBERS_STATISTICS_COUNT_KEY, $segmentId);
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
}

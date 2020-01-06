<?php

namespace MailPoet\DynamicSegments\FreePluginConnectors;

use MailPoet\DynamicSegments\Persistence\Loading\SingleSegmentLoader;
use MailPoet\DynamicSegments\Persistence\Loading\SubscribersIds;
use MailPoet\Models\DynamicSegment;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;

class SendingNewslettersSubscribersFinder {

  /** @var SingleSegmentLoader */
  private $singleSegmentLoader;

  /** @var SubscribersIds */
  private $subscribersIdsLoader;

  public function __construct(SingleSegmentLoader $singleSegmentLoader, SubscribersIds $subscribersIdsLoader) {
    $this->singleSegmentLoader = $singleSegmentLoader;
    $this->subscribersIdsLoader = $subscribersIdsLoader;
  }

  /**
   * @param Segment $segment
   * @param int[] $subscribersToProcessIds
   *
   * @return Subscriber[]
   */
  public function findSubscribersInSegment(Segment $segment, array $subscribersToProcessIds) {
    if ($segment->type !== DynamicSegment::TYPE_DYNAMIC) return [];
    $dynamicSegment = $this->singleSegmentLoader->load($segment->id);
    return $this->subscribersIdsLoader->load($dynamicSegment, $subscribersToProcessIds);
  }

  /**
   * @param Segment $segment
   *
   * @return array
   */
  public function getSubscriberIdsInSegment(Segment $segment) {
    if ($segment->type !== DynamicSegment::TYPE_DYNAMIC) return [];
    $dynamicSegment = $this->singleSegmentLoader->load($segment->id);
    $result = $this->subscribersIdsLoader->load($dynamicSegment);
    return $this->createResultArray($result);
  }

  private function createResultArray($subscribers) {
    $result = [];
    foreach ($subscribers as $subscriber) {
      $result[] = $subscriber->asArray();
    }
    return $result;
  }

}

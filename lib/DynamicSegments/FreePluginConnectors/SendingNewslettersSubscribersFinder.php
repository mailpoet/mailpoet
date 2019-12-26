<?php

namespace MailPoet\DynamicSegments\FreePluginConnectors;

use MailPoet\DynamicSegments\Persistence\Loading\SingleSegmentLoader;
use MailPoet\DynamicSegments\Persistence\Loading\SubscribersIds;
use MailPoet\Models\DynamicSegment;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;

class SendingNewslettersSubscribersFinder {

  /** @var SingleSegmentLoader */
  private $single_segment_loader;

  /** @var SubscribersIds */
  private $subscribers_ids_loader;

  public function __construct(SingleSegmentLoader $single_segment_loader, SubscribersIds $subscribers_ids_loader) {
    $this->single_segment_loader = $single_segment_loader;
    $this->subscribers_ids_loader = $subscribers_ids_loader;
  }

  /**
   * @param Segment $segment
   * @param int[] $subscribers_to_process_ids
   *
   * @return Subscriber[]
   */
  public function findSubscribersInSegment(Segment $segment, array $subscribers_to_process_ids) {
    if ($segment->type !== DynamicSegment::TYPE_DYNAMIC) return [];
    $dynamic_segment = $this->single_segment_loader->load($segment->id);
    return $this->subscribers_ids_loader->load($dynamic_segment, $subscribers_to_process_ids);
  }

  /**
   * @param Segment $segment
   *
   * @return array
   */
  public function getSubscriberIdsInSegment(Segment $segment) {
    if ($segment->type !== DynamicSegment::TYPE_DYNAMIC) return [];
    $dynamic_segment = $this->single_segment_loader->load($segment->id);
    $result = $this->subscribers_ids_loader->load($dynamic_segment);
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

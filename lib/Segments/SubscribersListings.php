<?php

namespace MailPoet\Segments;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Listing\Handler;
use MailPoet\Models\Segment;

class SubscribersListings {

  /** @var Handler */
  private $handler;

  public function __construct(Handler $handler) {
    $this->handler = $handler;
  }

  public function getListingsInSegment($data) {
    if (!isset($data['filter']['segment'])) {
      throw new \InvalidArgumentException('Missing segment id');
    }
    $segment = Segment::findOne($data['filter']['segment']);
    return $this->getListings($data, $segment instanceof Segment ? $segment : null);

  }

  private function getListings($data, Segment $segment = null) {
    if (!$segment
      || in_array($segment->type, [SegmentEntity::TYPE_DEFAULT, SegmentEntity::TYPE_WP_USERS, SegmentEntity::TYPE_WC_USERS], true)
    ) {
      return $listingData = $this->handler->get('\MailPoet\Models\Subscriber', $data);
    }
    if ($segment->type === SegmentEntity::TYPE_DYNAMIC) {
      return $this->handler->get('\MailPoet\Models\SubscribersInDynamicSegment', $data);
    }
    throw new \InvalidArgumentException('No handler found for segment');
  }
}

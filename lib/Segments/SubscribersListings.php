<?php

namespace MailPoet\Segments;

use MailPoet\Listing\Handler;
use MailPoet\Models\Segment;
use MailPoet\WP\Hooks;

class SubscribersListings {

  /** @var Handler */
  private $handler;

  function __construct(Handler $handler) {
    $this->handler = $handler;
  }

  function getListingsInSegment($data) {
    if(!isset($data['filter']['segment'])) {
      throw new \InvalidArgumentException('Missing segment id');
    }
    $segment = Segment::findOne($data['filter']['segment']);
    return $this->getListings($data, $segment ?: null);

  }

  private function getListings($data, Segment $segment = null) {
    if(!$segment
      || in_array($segment->type, [Segment::TYPE_DEFAULT, Segment::TYPE_WP_USERS, Segment::TYPE_WC_USERS], true)
    ) {
      return $listing_data = $this->handler->get('\MailPoet\Models\Subscriber', $data);
    }
    $handlers = Hooks::applyFilters('mailpoet_get_subscribers_listings_in_segment_handlers', array());
    foreach($handlers as $handler) {
      $listings = $handler->get($segment, $data);
      if($listings) {
        return $listings;
      }
    }
    throw new \InvalidArgumentException('No handler found for segment');
  }

}

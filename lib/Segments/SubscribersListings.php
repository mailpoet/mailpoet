<?php

namespace MailPoet\Segments;

use MailPoet\Listing\Handler;
use MailPoet\Models\Segment;
use MailPoet\WP\Hooks;

class SubscribersListings {

  function getListingsInSegment($data) {
    if(!isset($data['filter']['segment'])) {
      throw new \InvalidArgumentException('Missing segment id');
    }
    $segment = Segment::findOne($data['filter']['segment']);
    if($segment) {
      $segment = $segment->asArray();
    }
    return $this->getListings($segment, $data);

  }

  private function getListings($segment, $data) {
    if(!$segment || $segment['type'] === Segment::TYPE_DEFAULT || $segment['type'] === Segment::TYPE_WP_USERS) {
      $listing = new Handler('\MailPoet\Models\Subscriber', $data);

      return $listing_data = $listing->get();
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
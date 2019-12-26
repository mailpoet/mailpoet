<?php

namespace MailPoet\DynamicSegments\FreePluginConnectors;

use MailPoet\Listing\Handler;
use MailPoet\Models\DynamicSegment;
use MailPoet\Models\Segment;

class SubscribersListingsHandlerFactory {

  public function get(Segment $segment, $data) {
    if ($segment->type === DynamicSegment::TYPE_DYNAMIC) {
      $listing = new Handler();
      return $listing_data = $listing->get('\MailPoet\Models\SubscribersInDynamicSegment', $data);
    }
  }
}

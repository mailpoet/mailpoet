<?php

namespace MailPoet\Premium\DynamicSegments\FreePluginConnectors;

use MailPoet\Listing\Handler;
use MailPoet\Models\Segment;
use MailPoet\Premium\Models\DynamicSegment;

class SubscribersListingsHandlerFactory {

  function get(Segment $segment, $data) {
    if ($segment->type === DynamicSegment::TYPE_DYNAMIC) {
      $listing = new Handler();
      return $listing_data = $listing->get('\MailPoet\Premium\Models\SubscribersInDynamicSegment', $data);
    }
  }
}

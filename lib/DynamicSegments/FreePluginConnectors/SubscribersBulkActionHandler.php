<?php

namespace MailPoet\Premium\DynamicSegments\FreePluginConnectors;

use MailPoet\Listing\BulkActionController;
use MailPoet\Listing\BulkActionFactory;
use MailPoet\Listing\Handler;
use MailPoet\Premium\Models\DynamicSegment;

class SubscribersBulkActionHandler {

  /**
   * @param array $segment
   * @param array $data
   *
   * @return array
   * @throws \Exception
   */
  function apply(array $segment, array $data) {
    if ($segment['type'] === DynamicSegment::TYPE_DYNAMIC) {
      $bulkAction = new BulkActionController(new BulkActionFactory(), new Handler());
      return $bulkAction->apply('\MailPoet\Premium\Models\SubscribersInDynamicSegment', $data);
    }
  }

}

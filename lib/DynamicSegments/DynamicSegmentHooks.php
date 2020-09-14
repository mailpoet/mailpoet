<?php

namespace MailPoet\DynamicSegments;

use MailPoet\DynamicSegments\FreePluginConnectors\SubscribersBulkActionHandler;
use MailPoet\DynamicSegments\Mappers\DBMapper;
use MailPoet\DynamicSegments\Persistence\Loading\SingleSegmentLoader;
use MailPoet\WP\Functions as WPFunctions;

class DynamicSegmentHooks {
  /** @var WPFunctions */
  private $wp;

  public function __construct(WPFunctions $wp) {
    $this->wp = $wp;
  }

  public function init() {
    $this->wp->addAction(
      'mailpoet_subscribers_in_segment_apply_bulk_action_handlers',
      [$this, 'applySubscriberBulkAction']
    );

    $this->wp->addAction(
      'mailpoet_get_segment_filters',
      [$this, 'getSegmentFilters']
    );
  }

  public function applySubscriberBulkAction(array $handlers) {
    $handlers[] = new SubscribersBulkActionHandler();
    return $handlers;
  }

  public function getSegmentFilters($segmentId) {
    $singleSegmentLoader = new SingleSegmentLoader(new DBMapper());
    return $singleSegmentLoader->load($segmentId)->getFilters();
  }
}

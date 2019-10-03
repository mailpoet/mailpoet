<?php

namespace MailPoet\DynamicSegments;

use MailPoet\DynamicSegments\FreePluginConnectors\AddToNewslettersSegments;
use MailPoet\DynamicSegments\FreePluginConnectors\AddToSubscribersFilters;
use MailPoet\DynamicSegments\FreePluginConnectors\SendingNewslettersSubscribersFinder;
use MailPoet\DynamicSegments\FreePluginConnectors\SubscribersBulkActionHandler;
use MailPoet\DynamicSegments\FreePluginConnectors\SubscribersListingsHandlerFactory;
use MailPoet\DynamicSegments\Mappers\DBMapper;
use MailPoet\DynamicSegments\Persistence\Loading\Loader;
use MailPoet\DynamicSegments\Persistence\Loading\SingleSegmentLoader;
use MailPoet\DynamicSegments\Persistence\Loading\SubscribersCount;
use MailPoet\DynamicSegments\Persistence\Loading\SubscribersIds;
use MailPoet\WP\Functions as WPFunctions;

class DynamicSegmentHooks {
  /** @var WPFunctions */
  private $wp;

  function __construct(WPFunctions $wp) {
    $this->wp = $wp;
  }

  function init() {
    $this->wp->addAction(
      'mailpoet_segments_with_subscriber_count',
      [$this, 'addSegmentsWithSubscribersCount']
    );

    $this->wp->addAction(
      'mailpoet_get_subscribers_in_segment_finders',
      [$this, 'getSubscribersInSegmentsFinders']
    );

    $this->wp->addAction(
      'mailpoet_get_subscribers_listings_in_segment_handlers',
      [$this, 'getSubscribersListingsInSegmentsHandlers']
    );

    $this->wp->addAction(
      'mailpoet_subscribers_listings_filters_segments',
      [$this, 'addDynamicFiltersToSubscribersListingsFilters']
    );

    $this->wp->addAction(
      'mailpoet_subscribers_in_segment_apply_bulk_action_handlers',
      [$this, 'applySubscriberBulkAction']
    );

    $this->wp->addAction(
      'mailpoet_get_segment_filters',
      [$this, 'getSegmentFilters']
    );
  }

  function addSegmentsWithSubscribersCount($initial_segments) {
    $newsletters_add_segments = new AddToNewslettersSegments(new Loader(new DBMapper()), new SubscribersCount());
    return $newsletters_add_segments->add($initial_segments);
  }

  function getSubscribersInSegmentsFinders(array $finders) {
    $finders[] = new SendingNewslettersSubscribersFinder(new SingleSegmentLoader(new DBMapper()), new SubscribersIds());
    return $finders;
  }

  function getSubscribersListingsInSegmentsHandlers(array $handlers) {
    $handlers[] = new SubscribersListingsHandlerFactory();
    return $handlers;
  }

  function addDynamicFiltersToSubscribersListingsFilters($segment_filters) {
    $newsletters_add_segments = new AddToSubscribersFilters(new Loader(new DBMapper()), new SubscribersCount());
    return $newsletters_add_segments->add($segment_filters);
  }

  function applySubscriberBulkAction(array $handlers) {
    $handlers[] = new SubscribersBulkActionHandler();
    return $handlers;
  }

  function getSegmentFilters($segment_id) {
    $single_segment_loader = new SingleSegmentLoader(new DBMapper());
    return $single_segment_loader->load($segment_id)->getFilters();
  }
}

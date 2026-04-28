<?php declare(strict_types = 1);

namespace MailPoet\Segments\RestApi;

use MailPoet\API\REST\API as RestApi;
use MailPoet\Segments\RestApi\Endpoints\DynamicSegmentsBulkActionEndpoint;
use MailPoet\Segments\RestApi\Endpoints\DynamicSegmentsListingEndpoint;
use MailPoet\Segments\RestApi\Endpoints\SegmentsBulkActionEndpoint;
use MailPoet\Segments\RestApi\Endpoints\SegmentsListingEndpoint;
use MailPoet\WP\Functions as WPFunctions;

class Api {
  /** @var RestApi */
  private $api;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    RestApi $api,
    WPFunctions $wp
  ) {
    $this->api = $api;
    $this->wp = $wp;
  }

  public function initialize(): void {
    $this->wp->addAction(RestApi::REST_API_INIT_ACTION, function (): void {
      $this->api->registerGetRoute('segments', SegmentsListingEndpoint::class);
      $this->api->registerPostRoute('segments/bulk-action', SegmentsBulkActionEndpoint::class);
      $this->api->registerGetRoute('dynamic-segments', DynamicSegmentsListingEndpoint::class);
      $this->api->registerPostRoute('dynamic-segments/bulk-action', DynamicSegmentsBulkActionEndpoint::class);
    });
  }
}

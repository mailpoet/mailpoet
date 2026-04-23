<?php declare(strict_types = 1);

namespace MailPoet\Tags\RestApi;

use MailPoet\API\REST\API as RestApi;
use MailPoet\Tags\RestApi\Endpoints\TagDeleteEndpoint;
use MailPoet\Tags\RestApi\Endpoints\TagPutEndpoint;
use MailPoet\Tags\RestApi\Endpoints\TagsBulkDeleteEndpoint;
use MailPoet\Tags\RestApi\Endpoints\TagsGetEndpoint;
use MailPoet\Tags\RestApi\Endpoints\TagsPostEndpoint;
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
      $this->api->registerGetRoute('tags', TagsGetEndpoint::class);
      $this->api->registerPostRoute('tags', TagsPostEndpoint::class);
      $this->api->registerPutRoute('tags/(?P<id>\d+)', TagPutEndpoint::class);
      $this->api->registerDeleteRoute('tags/(?P<id>\d+)', TagDeleteEndpoint::class);
      $this->api->registerPostRoute('tags/bulk-delete', TagsBulkDeleteEndpoint::class);
    });
  }
}

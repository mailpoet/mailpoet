<?php declare(strict_types = 1);

namespace MailPoet\CustomFields\RestApi;

use MailPoet\API\REST\API as RestApi;
use MailPoet\CustomFields\RestApi\Endpoints\CustomFieldsBulkActionEndpoint;
use MailPoet\CustomFields\RestApi\Endpoints\CustomFieldsGetEndpoint;
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
      $this->api->registerGetRoute('custom-fields', CustomFieldsGetEndpoint::class);
      $this->api->registerPostRoute('custom-fields/bulk-action', CustomFieldsBulkActionEndpoint::class);
    });
  }
}

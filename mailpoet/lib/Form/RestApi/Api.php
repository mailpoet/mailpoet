<?php declare(strict_types = 1);

namespace MailPoet\Form\RestApi;

use MailPoet\API\REST\API as RestApi;
use MailPoet\Form\RestApi\Endpoints\FormsBulkActionEndpoint;
use MailPoet\Form\RestApi\Endpoints\FormsListingEndpoint;
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
      $this->api->registerGetRoute('forms', FormsListingEndpoint::class);
      $this->api->registerPostRoute('forms/bulk-action', FormsBulkActionEndpoint::class);
    });
  }
}

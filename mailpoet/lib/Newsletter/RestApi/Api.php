<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\RestApi;

use MailPoet\API\REST\API as RestApi;
use MailPoet\Newsletter\RestApi\Endpoints\NewsletterDuplicateEndpoint;
use MailPoet\Newsletter\RestApi\Endpoints\NewslettersBulkActionEndpoint;
use MailPoet\Newsletter\RestApi\Endpoints\NewslettersListingEndpoint;
use MailPoet\Newsletter\RestApi\Endpoints\NewsletterStatusEndpoint;
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
      $this->api->registerGetRoute('newsletters', NewslettersListingEndpoint::class);
      $this->api->registerPostRoute('newsletters/bulk-action', NewslettersBulkActionEndpoint::class);
      $this->api->registerPostRoute('newsletters/(?P<id>\d+)/duplicate', NewsletterDuplicateEndpoint::class);
      $this->api->registerPutRoute('newsletters/(?P<id>\d+)/status', NewsletterStatusEndpoint::class);
    });
  }
}

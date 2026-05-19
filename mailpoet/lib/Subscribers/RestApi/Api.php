<?php declare(strict_types = 1);

namespace MailPoet\Subscribers\RestApi;

use MailPoet\API\REST\API as RestApi;
use MailPoet\Subscribers\RestApi\Endpoints\SubscriberConfirmationEmailEndpoint;
use MailPoet\Subscribers\RestApi\Endpoints\SubscribersBulkActionEndpoint;
use MailPoet\Subscribers\RestApi\Endpoints\SubscribersListingEndpoint;
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
      $this->api->registerGetRoute('subscribers', SubscribersListingEndpoint::class);
      $this->api->registerPostRoute('subscribers/bulk-action', SubscribersBulkActionEndpoint::class);
      $this->api->registerPostRoute(
        'subscribers/(?P<id>\d+)/resend-confirmation-email',
        SubscriberConfirmationEmailEndpoint::class
      );
    });
  }
}

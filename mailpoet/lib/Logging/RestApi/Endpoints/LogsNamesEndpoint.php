<?php declare(strict_types = 1);

namespace MailPoet\Logging\RestApi\Endpoints;

use MailPoet\API\REST\Endpoint;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Config\AccessControl;
use MailPoet\Logging\LogRepository;
use MailPoet\WP\Functions as WPFunctions;

class LogsNamesEndpoint extends Endpoint {
  /** @var LogRepository */
  private $logRepository;

  public function __construct(
    LogRepository $logRepository
  ) {
    $this->logRepository = $logRepository;
  }

  public function handle(Request $request): Response {
    return new Response([
      'names' => $this->logRepository->getDistinctNames(),
    ]);
  }

  public function checkPermissions(): bool {
    return WPFunctions::get()->currentUserCan(AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN);
  }
}

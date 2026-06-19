<?php declare(strict_types = 1);

namespace MailPoet\Logging\RestApi\Endpoints;

use MailPoet\API\REST\ApiException;
use MailPoet\API\REST\Endpoint;
use MailPoet\API\REST\ListingRequestValidationTrait;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Config\AccessControl;
use MailPoet\Logging\LogRepository;
use MailPoet\Logging\RestApi\LogsFilterTrait;
use MailPoet\Validator\Builder;
use MailPoet\WP\Functions as WPFunctions;

class LogsDeleteEndpoint extends Endpoint {
  use ListingRequestValidationTrait;
  use LogsFilterTrait;

  // Referenced by ListingRequestValidationTrait's pagination helpers, which this
  // endpoint composes for filter/date validation but never calls (it does not
  // paginate). Values mirror AbstractListingEndpoint.
  private const MAX_PER_PAGE = 100;
  private const MAX_PAGE = 100000;

  /** @var LogRepository */
  private $logRepository;

  public function __construct(
    LogRepository $logRepository
  ) {
    $this->logRepository = $logRepository;
  }

  public function handle(Request $request): Response {
    $filter = $this->validateAndNormalizeLogsFilter($request->getParam('filter'));
    $search = $this->validateSearch($request->getParam('search'));

    $isUnrestricted = $filter === [] && $search === null;
    if ($isUnrestricted && $request->getParam('all') !== true) {
      throw new ApiException(
        __('Deleting all logs requires confirmation.', 'mailpoet'),
        400,
        'mailpoet_logs_delete_confirmation_required'
      );
    }

    return new Response([
      'deleted' => $this->logRepository->deleteLogs($filter, $search),
    ]);
  }

  public function checkPermissions(): bool {
    return WPFunctions::get()->currentUserCan(AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN);
  }

  public static function getRequestSchema(): array {
    return [
      'filter' => Builder::object(),
      'search' => Builder::string(),
      'all' => Builder::boolean(),
    ];
  }

  protected function getListingValidationErrorPrefix(): string {
    return 'logs';
  }

  /** @param mixed $search */
  private function validateSearch($search): ?string {
    if ($search === null || (is_string($search) && trim($search) === '')) {
      return null;
    }
    if (!is_string($search)) {
      throw $this->listingValidationError(__('Search must be a string.', 'mailpoet'), 'search');
    }
    return $search;
  }
}

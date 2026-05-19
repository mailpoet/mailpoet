<?php declare(strict_types = 1);

namespace MailPoet\Subscribers\RestApi\Endpoints;

use MailPoet\API\REST\ApiException;
use MailPoet\API\REST\Endpoint;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Config\AccessControl;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Listing\Handler as ListingHandler;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Subscribers\BulkActionController;
use MailPoet\Subscribers\BulkActionException;
use MailPoet\Subscribers\BulkConfirmationEmailResender;
use MailPoet\Validator\Builder;
use MailPoet\WP\Functions as WPFunctions;

class SubscribersBulkActionEndpoint extends Endpoint {
  public const ACTION_RESEND_CONFIRMATION_EMAILS = 'resendConfirmationEmails';

  /** @var ListingHandler */
  private $listingHandler;

  /** @var BulkActionController */
  private $bulkActionController;

  /** @var BulkConfirmationEmailResender */
  private $bulkConfirmationEmailResender;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    ListingHandler $listingHandler,
    BulkActionController $bulkActionController,
    BulkConfirmationEmailResender $bulkConfirmationEmailResender,
    WPFunctions $wp
  ) {
    $this->listingHandler = $listingHandler;
    $this->bulkActionController = $bulkActionController;
    $this->bulkConfirmationEmailResender = $bulkConfirmationEmailResender;
    $this->wp = $wp;
  }

  public function checkPermissions(): bool {
    return $this->wp->currentUserCan(AccessControl::PERMISSION_MANAGE_SUBSCRIBERS);
  }

  public function handle(Request $request): Response {
    $actionParam = $request->getParam('action');
    $action = is_string($actionParam) ? $actionParam : '';
    $definition = $this->buildDefinition($request);

    if ($action === self::ACTION_RESEND_CONFIRMATION_EMAILS) {
      return $this->handleResendConfirmation($request, $definition);
    }

    $data = [];
    $segmentIdParam = $request->getParam('segment_id');
    if (is_numeric($segmentIdParam)) {
      $data['segment_id'] = (int)$segmentIdParam;
    }
    $tagIdParam = $request->getParam('tag_id');
    if (is_numeric($tagIdParam)) {
      $data['tag_id'] = (int)$tagIdParam;
    }

    try {
      $result = $this->bulkActionController->execute($action, $definition, $data);
    } catch (BulkActionException $exception) {
      throw new ApiException(
        $exception->getMessage(),
        $exception->getStatusCode(),
        $exception->getErrorCode()
      );
    }

    return new Response([
      'action' => $action,
      'count' => $result['count'],
      'segment' => $result['segment'] ?? null,
      'tag' => $result['tag'] ?? null,
    ]);
  }

  public static function getRequestSchema(): array {
    return [
      'action' => Builder::string()->required(),
      'selection' => Builder::array(Builder::integer()),
      'group' => Builder::string(),
      'search' => Builder::string(),
      'filter' => Builder::object(),
      'segment_id' => Builder::integer(),
      'tag_id' => Builder::integer(),
    ];
  }

  private function handleResendConfirmation(Request $request, ListingDefinition $definition): Response {
    if (!$this->bulkConfirmationEmailResender->canCurrentUserResend()) {
      throw new ApiException(
        __('You do not have permission to resend confirmation emails.', 'mailpoet'),
        403,
        'mailpoet_subscribers_resend_forbidden'
      );
    }
    if ($definition->getGroup() !== SubscriberEntity::STATUS_UNCONFIRMED) {
      throw new ApiException(
        __('Confirmation emails can be resent in bulk only from the Unconfirmed subscribers view.', 'mailpoet'),
        400,
        'mailpoet_subscribers_invalid_group'
      );
    }
    if (!$this->bulkConfirmationEmailResender->isSignupConfirmationEnabled()) {
      throw new ApiException(
        $this->bulkConfirmationEmailResender->getConfirmationDisabledMessage(),
        400,
        'mailpoet_subscribers_confirmation_disabled'
      );
    }
    // BulkConfirmationEmailResender::queue() inspects $requestData['listing']
    // to detect whether the caller provided an explicit selection (so that
    // empty selection at the listing scope can target every matching
    // subscriber). Rebuild that shape from the flat REST schema.
    $listing = [
      'group' => $request->getParam('group'),
      'search' => $request->getParam('search'),
      'filter' => $request->getParam('filter'),
    ];
    $selection = $request->getParam('selection');
    if (is_array($selection)) {
      $listing['selection'] = $this->toIntList($selection);
    }
    $queueResult = $this->bulkConfirmationEmailResender->queue($definition, ['listing' => $listing]);

    return new Response([
      'action' => self::ACTION_RESEND_CONFIRMATION_EMAILS,
      'count' => $queueResult['queued_count'],
      'segment' => null,
      'tag' => null,
      'queue' => $queueResult,
    ]);
  }

  private function buildDefinition(Request $request): ListingDefinition {
    $filter = $request->getParam('filter');
    $selection = $request->getParam('selection');
    $searchParam = $request->getParam('search');
    $groupParam = $request->getParam('group');

    return $this->listingHandler->getListingDefinition([
      'offset' => 0,
      'limit' => 0,
      'sort_by' => 'id',
      'sort_order' => 'desc',
      'search' => is_string($searchParam) ? $searchParam : null,
      'group' => is_string($groupParam) ? $groupParam : null,
      'filter' => is_array($filter) ? $filter : [],
      'selection' => is_array($selection) ? $this->toIntList($selection) : [],
      'params' => [],
    ]);
  }

  /**
   * @param array<mixed> $values
   * @return int[]
   */
  private function toIntList(array $values): array {
    $ints = [];
    foreach ($values as $value) {
      if (is_scalar($value)) {
        $ints[] = (int)$value;
      }
    }
    return $ints;
  }
}

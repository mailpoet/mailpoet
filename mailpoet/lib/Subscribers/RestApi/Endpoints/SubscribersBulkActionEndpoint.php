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

  private const VALID_SELECT_ALL_GROUPS = [
    'all',
    SubscriberEntity::STATUS_SUBSCRIBED,
    SubscriberEntity::STATUS_UNCONFIRMED,
    SubscriberEntity::STATUS_UNSUBSCRIBED,
    SubscriberEntity::STATUS_INACTIVE,
    SubscriberEntity::STATUS_BOUNCED,
    'trash',
  ];

  private const TRASH_ONLY_ACTIONS = [
    BulkActionController::ACTION_DELETE,
    BulkActionController::ACTION_RESTORE,
  ];

  private const NON_TRASH_ACTIONS = [
    BulkActionController::ACTION_TRASH,
    BulkActionController::ACTION_UNSUBSCRIBE,
    BulkActionController::ACTION_MOVE_TO_LIST,
    BulkActionController::ACTION_ADD_TO_LIST,
    BulkActionController::ACTION_REMOVE_FROM_LIST,
    BulkActionController::ACTION_REMOVE_FROM_ALL_LISTS,
    BulkActionController::ACTION_ADD_TAG,
    BulkActionController::ACTION_REMOVE_TAG,
  ];

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

    $selectAll = $request->getParam('select_all') === true;
    $selectionParam = $request->getParam('selection');
    $hasSelection = is_array($selectionParam) && $selectionParam !== [];
    if (!$hasSelection && !$selectAll) {
      throw new ApiException(
        __('No subscribers selected.', 'mailpoet'),
        400,
        'mailpoet_subscribers_no_selection'
      );
    }
    if ($selectAll) {
      $this->validateSelectAllScope($action, $definition);
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
      'select_all' => Builder::boolean(),
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
    $selectAll = $request->getParam('select_all') === true;
    $selection = $request->getParam('selection');
    $hasSelection = is_array($selection) && $selection !== [];
    // Resend runs before the main guard, so apply the same explicit-intent
    // rule here: without a selection and without select_all, an omitted
    // listing selection would otherwise target every matching subscriber.
    if (!$hasSelection && !$selectAll) {
      throw new ApiException(
        __('No subscribers selected.', 'mailpoet'),
        400,
        'mailpoet_subscribers_no_selection'
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
    if (!$selectAll && is_array($selection)) {
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

  private function validateSelectAllScope(string $action, ListingDefinition $definition): void {
    $group = $definition->getGroup();
    if (!is_string($group) || !in_array($group, self::VALID_SELECT_ALL_GROUPS, true)) {
      throw new ApiException(
        __('Select all requires a valid subscriber view.', 'mailpoet'),
        400,
        'mailpoet_subscribers_invalid_select_all_group'
      );
    }

    if (in_array($action, self::TRASH_ONLY_ACTIONS, true) && $group !== 'trash') {
      throw new ApiException(
        __('This bulk action can only be applied from the Trash view.', 'mailpoet'),
        400,
        'mailpoet_subscribers_invalid_select_all_scope'
      );
    }

    if (in_array($action, self::NON_TRASH_ACTIONS, true) && $group === 'trash') {
      throw new ApiException(
        __('This bulk action cannot be applied from the Trash view.', 'mailpoet'),
        400,
        'mailpoet_subscribers_invalid_select_all_scope'
      );
    }
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

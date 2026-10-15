<?php declare(strict_types = 1);

namespace MailPoet\Subscribers\RestApi\Endpoints;

use MailPoet\API\JSON\ResponseBuilders\SubscribersResponseBuilder;
use MailPoet\API\REST\AbstractListingEndpoint;
use MailPoet\API\REST\ListingRequestValidationTrait;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Config\AccessControl;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Listing\Handler as ListingHandler;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Listing\ListingRepository;
use MailPoet\Subscribers\RestApi\SubscribersFilterTrait;
use MailPoet\Subscribers\SubscriberListingRepository;
use MailPoet\WP\Functions as WPFunctions;

class SubscribersListingEndpoint extends AbstractListingEndpoint {
  use ListingRequestValidationTrait;
  use SubscribersFilterTrait;

  private const ALLOWED_SORT_FIELDS = ['id', 'email', 'status', 'engagement_score', 'last_subscribed_at', 'created_at'];
  private const ALLOWED_GROUPS = [
    'all',
    SubscriberEntity::STATUS_SUBSCRIBED,
    SubscriberEntity::STATUS_UNCONFIRMED,
    SubscriberEntity::STATUS_UNSUBSCRIBED,
    SubscriberEntity::STATUS_INACTIVE,
    SubscriberEntity::STATUS_BOUNCED,
    'trash',
  ];

  /** @var SubscriberListingRepository */
  private $subscriberListingRepository;

  /** @var SubscribersResponseBuilder */
  private $subscribersResponseBuilder;

  public function __construct(
    ListingHandler $listingHandler,
    SubscriberListingRepository $subscriberListingRepository,
    SubscribersResponseBuilder $subscribersResponseBuilder
  ) {
    parent::__construct($listingHandler);
    $this->subscriberListingRepository = $subscriberListingRepository;
    $this->subscribersResponseBuilder = $subscribersResponseBuilder;
  }

  public function handle(Request $request): Response {
    $this->validateRequest($request);
    return parent::handle($request);
  }

  public function checkPermissions(): bool {
    return WPFunctions::get()->currentUserCan(AccessControl::PERMISSION_MANAGE_SUBSCRIBERS);
  }

  protected function getListingRepository(): ListingRepository {
    return $this->subscriberListingRepository;
  }

  protected function getCountAndGroups(ListingRepository $repository, ListingDefinition $definition): array {
    if (!$repository instanceof SubscriberListingRepository) {
      return parent::getCountAndGroups($repository, $definition);
    }
    return $repository->getCountAndGroups($definition);
  }

  protected function buildItems(array $rows, ListingDefinition $definition): array {
    $subscribers = $this->subscribersResponseBuilder->buildForListing($rows);
    // Mirror the legacy endpoint: when filtering by a specific segment, surface
    // the per-segment unsubscribe status as the visible status so the UI shows
    // "unsubscribed from this list" rather than the global subscriber status.
    $segmentFilter = $this->subscriberListingRepository->getSingleListFilter($definition);
    if ($segmentFilter !== null) {
      foreach ($subscribers as $key => $subscriber) {
        if ($this->isUnsubscribedFromSegment($subscriber, $segmentFilter)) {
          $subscribers[$key]['status'] = SubscriberEntity::STATUS_UNSUBSCRIBED;
        }
      }
    }
    return $subscribers;
  }

  protected function getListingValidationErrorPrefix(): string {
    return 'subscribers';
  }

  private function validateRequest(Request $request): void {
    $this->validateSortField($request->getParam('orderby'), self::ALLOWED_SORT_FIELDS);
    $this->validateSortField($request->getParam('sort_by'), self::ALLOWED_SORT_FIELDS);
    $this->validateSortOrder($request->getParam('order'));
    $this->validateSortOrder($request->getParam('sort_order'));
    $this->validatePagination($request);
    $group = $request->getParam('group');
    if ($group !== null && $group !== '' && (!is_string($group) || !in_array($group, self::ALLOWED_GROUPS, true))) {
      throw $this->listingValidationError(__('Unsupported subscribers group.', 'mailpoet'), 'group');
    }
    $this->validateSubscribersFilter($request->getParam('filter'));
  }

  protected function getDefaultSortBy(): string {
    return 'created_at';
  }

  protected function getDefaultSortOrder(): string {
    return 'desc';
  }

  protected function getDefaultGroup(): ?string {
    return 'all';
  }

  /**
   * @param array<string, mixed> $subscriber
   */
  private function isUnsubscribedFromSegment(array $subscriber, string $segmentId): bool {
    if (!isset($subscriber['subscriptions']) || !is_array($subscriber['subscriptions'])) {
      return false;
    }
    foreach ($subscriber['subscriptions'] as $segment) {
      if (!is_array($segment)) continue;
      if (!isset($segment['segment_id']) || !is_scalar($segment['segment_id'])) continue;
      if ((string)$segment['segment_id'] !== $segmentId) continue;
      $status = $segment['status'] ?? null;
      return is_scalar($status) && (string)$status === SubscriberEntity::STATUS_UNSUBSCRIBED;
    }
    return false;
  }
}

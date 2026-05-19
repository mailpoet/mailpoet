<?php declare(strict_types = 1);

namespace MailPoet\Subscribers\RestApi\Endpoints;

use MailPoet\API\JSON\ResponseBuilders\SubscribersResponseBuilder;
use MailPoet\API\REST\AbstractListingEndpoint;
use MailPoet\Config\AccessControl;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Listing\Handler as ListingHandler;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Listing\ListingRepository;
use MailPoet\Subscribers\SubscriberListingRepository;
use MailPoet\WP\Functions as WPFunctions;

class SubscribersListingEndpoint extends AbstractListingEndpoint {
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

  public function checkPermissions(): bool {
    return WPFunctions::get()->currentUserCan(AccessControl::PERMISSION_MANAGE_SUBSCRIBERS);
  }

  protected function getListingRepository(): ListingRepository {
    return $this->subscriberListingRepository;
  }

  protected function buildItems(array $rows, ListingDefinition $definition): array {
    $subscribers = $this->subscribersResponseBuilder->buildForListing($rows);
    // Mirror the legacy endpoint: when filtering by a specific segment, surface
    // the per-segment unsubscribe status as the visible status so the UI shows
    // "unsubscribed from this list" rather than the global subscriber status.
    $segmentFilter = $this->extractSegmentFilter($definition);
    if ($segmentFilter !== null) {
      foreach ($subscribers as $key => $subscriber) {
        if ($this->isUnsubscribedFromSegment($subscriber, $segmentFilter)) {
          $subscribers[$key]['status'] = SubscriberEntity::STATUS_UNSUBSCRIBED;
        }
      }
    }
    return $subscribers;
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

  private function extractSegmentFilter(ListingDefinition $definition): ?string {
    $filters = $definition->getFilters();
    if (!isset($filters['segment']) || !is_scalar($filters['segment'])) {
      return null;
    }
    $segment = (string)$filters['segment'];
    return $segment !== '' ? $segment : null;
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

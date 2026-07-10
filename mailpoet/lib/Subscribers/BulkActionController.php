<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\TagEntity;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Statistics\Track\Unsubscribes;
use MailPoet\Tags\TagRepository;

/**
 * Centralized bulk-action dispatcher used by the REST endpoint at
 * `mailpoet/v1/subscribers/bulk-action`. Keeping the per-action branch +
 * reference-data resolution in one place keeps the REST endpoint a thin
 * HTTP adapter.
 */
class BulkActionController {
  public const ACTION_TRASH = 'trash';
  public const ACTION_RESTORE = 'restore';
  public const ACTION_DELETE = 'delete';
  public const ACTION_UNSUBSCRIBE = 'unsubscribe';
  public const ACTION_MOVE_TO_LIST = 'moveToList';
  public const ACTION_ADD_TO_LIST = 'addToList';
  public const ACTION_REMOVE_FROM_LIST = 'removeFromList';
  public const ACTION_REMOVE_FROM_ALL_LISTS = 'removeFromAllLists';
  public const ACTION_ADD_TAG = 'addTag';
  public const ACTION_REMOVE_TAG = 'removeTag';

  public const SUPPORTED_ACTIONS = [
    self::ACTION_TRASH,
    self::ACTION_RESTORE,
    self::ACTION_DELETE,
    self::ACTION_UNSUBSCRIBE,
    self::ACTION_MOVE_TO_LIST,
    self::ACTION_ADD_TO_LIST,
    self::ACTION_REMOVE_FROM_LIST,
    self::ACTION_REMOVE_FROM_ALL_LISTS,
    self::ACTION_ADD_TAG,
    self::ACTION_REMOVE_TAG,
  ];

  private const ACTIONS_REQUIRING_SEGMENT = [
    self::ACTION_MOVE_TO_LIST,
    self::ACTION_ADD_TO_LIST,
    self::ACTION_REMOVE_FROM_LIST,
  ];

  private const ACTIONS_REQUIRING_TAG = [
    self::ACTION_ADD_TAG,
    self::ACTION_REMOVE_TAG,
  ];

  /** @var SubscriberListingRepository */
  private $subscriberListingRepository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var TagRepository */
  private $tagRepository;

  /** @var Unsubscribes */
  private $unsubscribesTracker;

  public function __construct(
    SubscriberListingRepository $subscriberListingRepository,
    SubscribersRepository $subscribersRepository,
    SegmentsRepository $segmentsRepository,
    TagRepository $tagRepository,
    Unsubscribes $unsubscribesTracker
  ) {
    $this->subscriberListingRepository = $subscriberListingRepository;
    $this->subscribersRepository = $subscribersRepository;
    $this->segmentsRepository = $segmentsRepository;
    $this->tagRepository = $tagRepository;
    $this->unsubscribesTracker = $unsubscribesTracker;
  }

  /**
   * @param array{segment_id?: int|string, tag_id?: int|string, trigger_automations?: bool} $data
   * @return array{count: int, segment?: array{id: int, name: string}, tag?: array{id: int, name: string}}
   * @throws BulkActionException
   */
  public function execute(string $action, ListingDefinition $definition, array $data = []): array {
    if (!in_array($action, self::SUPPORTED_ACTIONS, true)) {
      throw new BulkActionException(
        // translators: %s is the offending bulk-action name.
        sprintf(__("Invalid bulk action '%s' provided.", 'mailpoet'), $action),
        'mailpoet_subscribers_invalid_bulk_action',
        400
      );
    }

    $segment = in_array($action, self::ACTIONS_REQUIRING_SEGMENT, true) || isset($data['segment_id'])
      ? $this->resolveSegment($data)
      : null;
    $tag = in_array($action, self::ACTIONS_REQUIRING_TAG, true) || isset($data['tag_id'])
      ? $this->resolveTag($data)
      : null;
    $skipHooks = !($data['trigger_automations'] ?? false);

    $ids = $this->subscriberListingRepository->getActionableIds($definition);
    $count = $this->dispatch($action, $ids, $segment, $tag, $skipHooks);

    $result = ['count' => $count];
    if ($segment instanceof SegmentEntity) {
      $result['segment'] = ['id' => (int)$segment->getId(), 'name' => (string)$segment->getName()];
    }
    if ($tag instanceof TagEntity) {
      $result['tag'] = ['id' => (int)$tag->getId(), 'name' => (string)$tag->getName()];
    }
    return $result;
  }

  /**
   * @param int[] $ids
   */
  private function dispatch(
    string $action,
    array $ids,
    ?SegmentEntity $segment,
    ?TagEntity $tag,
    bool $skipHooks
  ): int {
    switch ($action) {
      case self::ACTION_TRASH:
        return $this->subscribersRepository->bulkTrash($ids);
      case self::ACTION_RESTORE:
        return $this->subscribersRepository->bulkRestore($ids);
      case self::ACTION_DELETE:
        return $this->subscribersRepository->bulkDelete($ids);
      case self::ACTION_REMOVE_FROM_ALL_LISTS:
        return $this->subscribersRepository->bulkRemoveFromAllSegments($ids);
      case self::ACTION_UNSUBSCRIBE:
        $this->trackBulkUnsubscribe($ids);
        return $this->subscribersRepository->bulkUnsubscribe($ids);
      case self::ACTION_MOVE_TO_LIST:
        return $this->subscribersRepository->bulkMoveToSegment(
          $this->requireSegment($segment, $action),
          $ids,
          $skipHooks
        );
      case self::ACTION_ADD_TO_LIST:
        return $this->subscribersRepository->bulkAddToSegment(
          $this->requireSegment($segment, $action),
          $ids,
          $skipHooks
        );
      case self::ACTION_REMOVE_FROM_LIST:
        return $this->subscribersRepository->bulkRemoveFromSegment($this->requireSegment($segment, $action), $ids);
      case self::ACTION_ADD_TAG:
        return $this->subscribersRepository->bulkAddTag($this->requireTag($tag, $action), $ids, $skipHooks);
      case self::ACTION_REMOVE_TAG:
        return $this->subscribersRepository->bulkRemoveTag($this->requireTag($tag, $action), $ids, $skipHooks);
      default:
        // Reachable only if SUPPORTED_ACTIONS and this switch drift out of sync.
        throw new BulkActionException(
          // translators: %s is the offending bulk-action name.
          sprintf(__("Invalid bulk action '%s' provided.", 'mailpoet'), $action),
          'mailpoet_subscribers_invalid_bulk_action',
          400
        );
    }
  }

  /**
   * @param array{segment_id?: int|string} $data
   */
  private function resolveSegment(array $data): SegmentEntity {
    if (!isset($data['segment_id'])) {
      throw new BulkActionException(
        __('A list is required for this bulk action.', 'mailpoet'),
        'mailpoet_subscribers_missing_segment',
        400
      );
    }
    $segment = $this->segmentsRepository->findOneById((int)$data['segment_id']);
    if (!$segment instanceof SegmentEntity) {
      throw new BulkActionException(
        __('This list does not exist.', 'mailpoet'),
        'mailpoet_subscribers_segment_not_found',
        404
      );
    }
    return $segment;
  }

  /**
   * @param array{tag_id?: int|string} $data
   */
  private function resolveTag(array $data): TagEntity {
    if (!isset($data['tag_id'])) {
      throw new BulkActionException(
        __('A tag is required for this bulk action.', 'mailpoet'),
        'mailpoet_subscribers_missing_tag',
        400
      );
    }
    $tag = $this->tagRepository->findOneById((int)$data['tag_id']);
    if (!$tag instanceof TagEntity) {
      throw new BulkActionException(
        __('This tag does not exist.', 'mailpoet'),
        'mailpoet_subscribers_tag_not_found',
        404
      );
    }
    return $tag;
  }

  private function requireSegment(?SegmentEntity $segment, string $action): SegmentEntity {
    if (!$segment instanceof SegmentEntity) {
      throw new BulkActionException(
        // translators: %s is the action name.
        sprintf(__("The '%s' bulk action requires a list.", 'mailpoet'), $action),
        'mailpoet_subscribers_missing_segment',
        400
      );
    }
    return $segment;
  }

  private function requireTag(?TagEntity $tag, string $action): TagEntity {
    if (!$tag instanceof TagEntity) {
      throw new BulkActionException(
        // translators: %s is the action name.
        sprintf(__("The '%s' bulk action requires a tag.", 'mailpoet'), $action),
        'mailpoet_subscribers_missing_tag',
        400
      );
    }
    return $tag;
  }

  /**
   * @param int[] $ids
   */
  private function trackBulkUnsubscribe(array $ids): void {
    if ($ids === []) return;
    $subscribers = $this->subscribersRepository->findBy(['id' => $ids]);
    foreach ($subscribers as $subscriber) {
      if (
        $subscriber instanceof SubscriberEntity
        && $subscriber->getStatus() !== SubscriberEntity::STATUS_UNSUBSCRIBED
      ) {
        $this->unsubscribesTracker->track(
          (int)$subscriber->getId(),
          StatisticsUnsubscribeEntity::SOURCE_ADMINISTRATOR
        );
      }
    }
  }
}

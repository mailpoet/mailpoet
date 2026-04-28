<?php declare(strict_types = 1);

namespace MailPoet\Segments\RestApi\Endpoints;

use MailPoet\API\REST\ApiException;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Form\FormsRepository;
use MailPoet\Listing\Handler as ListingHandler;
use MailPoet\Newsletter\Segment\NewsletterSegmentRepository;
use MailPoet\Segments\SegmentListingRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Validator\Builder;

class SegmentsBulkActionEndpoint extends SegmentsEndpoint {
  private const ACTION_TRASH = 'trash';
  private const ACTION_RESTORE = 'restore';
  private const ACTION_DELETE = 'delete';
  private const ACTION_EMPTY_TRASH = 'empty_trash';

  private const SUPPORTED_ACTIONS = [
    self::ACTION_TRASH,
    self::ACTION_RESTORE,
    self::ACTION_DELETE,
    self::ACTION_EMPTY_TRASH,
  ];

  /** @var ListingHandler */
  private $listingHandler;

  /** @var SegmentListingRepository */
  private $segmentListingRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var NewsletterSegmentRepository */
  private $newsletterSegmentRepository;

  /** @var FormsRepository */
  private $formsRepository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function __construct(
    ListingHandler $listingHandler,
    SegmentListingRepository $segmentListingRepository,
    SegmentsRepository $segmentsRepository,
    NewsletterSegmentRepository $newsletterSegmentRepository,
    FormsRepository $formsRepository,
    SubscribersRepository $subscribersRepository
  ) {
    $this->listingHandler = $listingHandler;
    $this->segmentListingRepository = $segmentListingRepository;
    $this->segmentsRepository = $segmentsRepository;
    $this->newsletterSegmentRepository = $newsletterSegmentRepository;
    $this->formsRepository = $formsRepository;
    $this->subscribersRepository = $subscribersRepository;
  }

  public function handle(Request $request): Response {
    $action = $this->validateAction($request);
    $this->validateListingParams($request);
    $ids = $this->getSelectedIds($request, $action);

    $result = [
      'updated' => 0,
      'deleted' => 0,
      'skipped' => 0,
      'errors' => [],
    ];

    if ($action === self::ACTION_TRASH) {
      $this->trashSegments($ids, $result);
    } elseif ($action === self::ACTION_RESTORE) {
      $this->restoreSegments($ids, $result);
    } else {
      $this->deleteSegments($ids, $result);
    }

    return new Response($result);
  }

  public static function getRequestSchema(): array {
    return [
      'action' => Builder::string()->required(),
      'ids' => Builder::array(Builder::integer()),
      'select_all' => Builder::boolean(),
      'group' => Builder::string(),
      'page' => Builder::integer(),
      'per_page' => Builder::integer(),
      'orderby' => Builder::string(),
      'order' => Builder::string(),
    ];
  }

  private function validateAction(Request $request): string {
    $action = is_string($request->getParam('action')) ? (string)$request->getParam('action') : '';
    if (!in_array($action, self::SUPPORTED_ACTIONS, true)) {
      throw new ApiException(
        sprintf(
          // translators: %s is the list of supported bulk actions.
          __('Unsupported bulk action. Allowed values are: %s.', 'mailpoet'),
          implode(', ', self::SUPPORTED_ACTIONS)
        ),
        400,
        'mailpoet_segments_invalid_bulk_action'
      );
    }
    return $action;
  }

  private function validateListingParams(Request $request): void {
    $this->validateGroup(is_string($request->getParam('group')) ? (string)$request->getParam('group') : null);
    $this->validateOrder(is_string($request->getParam('order')) ? (string)$request->getParam('order') : null, 'asc');
    $this->validatePage($request->getParam('page'));
    $this->validatePerPage($request->getParam('per_page'), 20);

    $orderby = is_string($request->getParam('orderby')) && $request->getParam('orderby') !== ''
      ? (string)$request->getParam('orderby')
      : 'name';
    $allowedSortFields = ['name', 'created_at', 'updated_at', 'average_engagement_score'];
    if (!in_array($orderby, $allowedSortFields, true)) {
      throw new ApiException(
        sprintf(
          // translators: %s is the list of supported sort fields.
          __('Unsupported sort field. Allowed values are: %s.', 'mailpoet'),
          implode(', ', $allowedSortFields)
        ),
        400,
        'mailpoet_segments_invalid_orderby'
      );
    }
  }

  /**
   * @return int[]
   */
  private function getSelectedIds(Request $request, string $action): array {
    if ($action === self::ACTION_EMPTY_TRASH) {
      return $this->getAllMatchingIds('trash');
    }

    if ($request->getParam('select_all') === true) {
      $group = $this->validateGroup(is_string($request->getParam('group')) ? (string)$request->getParam('group') : null);
      return $this->getAllMatchingIds($group);
    }

    return $this->validateIds($request->getParam('ids'));
  }

  /**
   * @return int[]
   */
  private function getAllMatchingIds(string $group): array {
    $definition = $this->listingHandler->getListingDefinition([
      'group' => $group,
      'sort_by' => 'name',
      'sort_order' => 'asc',
      'params' => ['lists'],
    ]);
    $ids = $this->segmentListingRepository->getActionableIds($definition);
    return array_map('intval', $ids);
  }

  /**
   * @param int[] $ids
   * @param array{updated:int,deleted:int,skipped:int,errors:array<int, array{id:int|null,message:string}>} $result
   */
  private function trashSegments(array $ids, array &$result): void {
    $newsletterBlockers = $this->newsletterSegmentRepository->getSubjectsOfActivelyUsedEmailsForSegments($ids);
    $formBlockers = $this->formsRepository->getNamesOfFormsForSegments();

    foreach ($ids as $id) {
      $segment = $this->segmentsRepository->findOneById($id);
      if (!$segment instanceof SegmentEntity) {
        $this->skip($result, $id, __('This list does not exist.', 'mailpoet'));
        continue;
      }
      if (!in_array($segment->getType(), [SegmentEntity::TYPE_DEFAULT, SegmentEntity::TYPE_WP_USERS], true)) {
        $this->skip($result, $id, __('This list cannot be moved to trash.', 'mailpoet'));
        continue;
      }
      if (isset($newsletterBlockers[$id])) {
        $this->skip($result, $id, str_replace(
          '%1$s',
          "'" . join("', '", $newsletterBlockers[$id]) . "'",
          // translators: %1$s is a comma-separated list of emails for which the segment is used.
          _x('List cannot be deleted because it’s used for %1$s email', 'Alert shown when trying to delete segment, which is assigned to any automatic emails.', 'mailpoet')
        ));
        continue;
      }
      if (isset($formBlockers[$id])) {
        $this->skip($result, $id, str_replace(
          '%1$s',
          "'" . join("', '", $formBlockers[$id]) . "'",
          // translators: %1$s is a comma-separated list of forms for which the segment is used.
          _nx(
            'List cannot be deleted because it’s used for %1$s form',
            'List cannot be deleted because it’s used for %1$s forms',
            count($formBlockers[$id]),
            'Alert shown when trying to delete segment, when it is assigned to a form.',
            'mailpoet'
          )
        ));
        continue;
      }
      if ($segment->getType() === SegmentEntity::TYPE_WP_USERS) {
        $subscribers = $this->subscribersRepository->findExclusiveSubscribersBySegment((int)$segment->getId());
        $subscriberIds = array_map(static function ($subscriber): int {
          return (int)$subscriber->getId();
        }, $subscribers);
        $this->subscribersRepository->bulkTrash($subscriberIds);
      }
      $result['updated'] += $this->segmentsRepository->doTrash([$id], $segment->getType());
    }
  }

  /**
   * @param int[] $ids
   * @param array{updated:int,deleted:int,skipped:int,errors:array<int, array{id:int|null,message:string}>} $result
   */
  private function restoreSegments(array $ids, array &$result): void {
    foreach ($ids as $id) {
      $segment = $this->segmentsRepository->findOneById($id);
      if (!$segment instanceof SegmentEntity) {
        $this->skip($result, $id, __('This list does not exist.', 'mailpoet'));
        continue;
      }
      if (!in_array($segment->getType(), [SegmentEntity::TYPE_DEFAULT, SegmentEntity::TYPE_WP_USERS], true)) {
        $this->skip($result, $id, __('This list cannot be restored.', 'mailpoet'));
        continue;
      }
      if ($segment->getType() === SegmentEntity::TYPE_WP_USERS) {
        $subscribers = $this->subscribersRepository->findBySegment((int)$segment->getId());
        $subscriberIds = array_map(static function ($subscriber): int {
          return (int)$subscriber->getId();
        }, $subscribers);
        $this->subscribersRepository->bulkRestore($subscriberIds);
      }
      $result['updated'] += $this->segmentsRepository->bulkRestore([$id], $segment->getType());
    }
  }

  /**
   * @param int[] $ids
   * @param array{updated:int,deleted:int,skipped:int,errors:array<int, array{id:int|null,message:string}>} $result
   */
  private function deleteSegments(array $ids, array &$result): void {
    foreach ($ids as $id) {
      $segment = $this->segmentsRepository->findOneById($id);
      if (!$segment instanceof SegmentEntity) {
        $this->skip($result, $id, __('This list does not exist.', 'mailpoet'));
        continue;
      }
      if ($segment->getType() !== SegmentEntity::TYPE_DEFAULT) {
        $this->skip($result, $id, __('This list cannot be deleted.', 'mailpoet'));
        continue;
      }
      $result['deleted'] += $this->segmentsRepository->bulkDelete([$id]);
    }
  }

  /**
   * @param array{updated:int,deleted:int,skipped:int,errors:array<int, array{id:int|null,message:string}>} $result
   */
  private function skip(array &$result, ?int $id, string $message): void {
    $result['skipped']++;
    $result['errors'][] = [
      'id' => $id,
      'message' => $message,
    ];
  }
}

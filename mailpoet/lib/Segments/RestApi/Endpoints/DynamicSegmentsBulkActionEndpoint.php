<?php declare(strict_types = 1);

namespace MailPoet\Segments\RestApi\Endpoints;

use MailPoet\API\REST\ApiException;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Listing\Handler as ListingHandler;
use MailPoet\Newsletter\Segment\NewsletterSegmentRepository;
use MailPoet\Segments\DynamicSegments\DynamicSegmentsListingRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Validator\Builder;

class DynamicSegmentsBulkActionEndpoint extends SegmentsEndpoint {
  private const ACTION_TRASH = 'trash';
  private const ACTION_RESTORE = 'restore';
  private const ACTION_DELETE = 'delete';

  private const SUPPORTED_ACTIONS = [
    self::ACTION_TRASH,
    self::ACTION_RESTORE,
    self::ACTION_DELETE,
  ];

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var ListingHandler */
  private $listingHandler;

  /** @var DynamicSegmentsListingRepository */
  private $dynamicSegmentsListingRepository;

  /** @var NewsletterSegmentRepository */
  private $newsletterSegmentRepository;

  public function __construct(
    ListingHandler $listingHandler,
    DynamicSegmentsListingRepository $dynamicSegmentsListingRepository,
    SegmentsRepository $segmentsRepository,
    NewsletterSegmentRepository $newsletterSegmentRepository
  ) {
    $this->listingHandler = $listingHandler;
    $this->dynamicSegmentsListingRepository = $dynamicSegmentsListingRepository;
    $this->segmentsRepository = $segmentsRepository;
    $this->newsletterSegmentRepository = $newsletterSegmentRepository;
  }

  public function handle(Request $request): Response {
    $action = $this->validateAction($request);
    $this->validateGroup(is_string($request->getParam('group')) ? (string)$request->getParam('group') : null);
    $orderParam = $request->getParam('order') ?? $request->getParam('sort_order');
    $this->validateOrder(is_string($orderParam) ? (string)$orderParam : null, 'desc');
    $this->validatePage($request->getParam('page'));
    $this->validateOffset($request->getParam('offset'));
    $this->validatePerPage($request->getParam('per_page') ?? $request->getParam('limit'), 25);
    $orderbyParam = $request->getParam('orderby') ?? $request->getParam('sort_by');
    $orderby = is_string($orderbyParam) && $orderbyParam !== ''
      ? (string)$orderbyParam
      : 'updated_at';
    $allowedSortFields = ['name', 'created_at', 'updated_at'];
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
    $ids = $this->getSelectedIds($request, $orderby, is_string($orderParam) ? strtolower($orderParam) : 'desc');
    $this->validateDynamicSelection($ids);
    if ($action === self::ACTION_DELETE) {
      $this->validateDeletionSelection($ids);
    }

    $result = [
      'updated' => 0,
      'deleted' => 0,
      'skipped' => 0,
      'errors' => [],
    ];

    if ($action === self::ACTION_TRASH) {
      $this->trashSegments($ids, $result);
    } elseif ($action === self::ACTION_RESTORE) {
      $result['updated'] = $this->segmentsRepository->bulkRestore($ids, SegmentEntity::TYPE_DYNAMIC);
    } else {
      $result['deleted'] = $this->segmentsRepository->bulkDelete($ids, SegmentEntity::TYPE_DYNAMIC);
    }

    return new Response($result);
  }

  public static function getRequestSchema(): array {
    return [
      'action' => Builder::string()->required(),
      'ids' => Builder::array(Builder::integer()),
      'select_all' => Builder::boolean(),
      'group' => Builder::string(),
      'search' => Builder::string(),
      'page' => Builder::integer(),
      'per_page' => Builder::integer(),
      'limit' => Builder::integer(),
      'offset' => Builder::integer(),
      'orderby' => Builder::string(),
      'order' => Builder::string(),
      'sort_by' => Builder::string(),
      'sort_order' => Builder::string(),
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
        'mailpoet_dynamic_segments_invalid_bulk_action'
      );
    }
    return $action;
  }

  /**
   * @return int[]
   */
  private function getSelectedIds(Request $request, string $sortBy, string $sortOrder): array {
    if ($request->getParam('select_all') !== true) {
      return $this->validateIds($request->getParam('ids'));
    }

    $definition = $this->listingHandler->getListingDefinition([
      'group' => $this->validateGroup(is_string($request->getParam('group')) ? (string)$request->getParam('group') : null),
      'search' => is_string($request->getParam('search')) ? (string)$request->getParam('search') : null,
      'sort_by' => $sortBy,
      'sort_order' => $sortOrder,
      'params' => ['segments'],
    ]);
    $ids = $this->dynamicSegmentsListingRepository->getActionableIds($definition);
    $ids = array_map('intval', $ids);
    if ($ids === []) {
      throw new ApiException(
        __('At least one segment id is required.', 'mailpoet'),
        400,
        'mailpoet_segments_ids_required'
      );
    }
    return $ids;
  }

  /**
   * @param int[] $ids
   * @param array{updated:int,deleted:int,skipped:int,errors:array<int, array{id:int|null,message:string}>} $result
   */
  private function trashSegments(array $ids, array &$result): void {
    $newsletterBlockers = $this->newsletterSegmentRepository->getSubjectsOfActivelyUsedEmailsForSegments($ids);
    $trashIds = [];
    foreach ($ids as $id) {
      if (isset($newsletterBlockers[$id])) {
        $segment = $this->segmentsRepository->findOneById($id);
        $this->skip($result, $id, sprintf(
          // translators: %1$s is the name of the segment, %2$s is a comma-separated list of emails for which the segment is used.
          _x('Segment \'%1$s\' cannot be deleted because it’s used for \'%2$s\' email', 'Alert shown when trying to delete segment, which is assigned to any automatic emails.', 'mailpoet'),
          $segment instanceof SegmentEntity ? $segment->getName() : (string)$id,
          join("', '", $newsletterBlockers[$id])
        ));
        continue;
      }
      $trashIds[] = $id;
    }
    $result['updated'] = $this->segmentsRepository->bulkTrash($trashIds, SegmentEntity::TYPE_DYNAMIC);
  }

  /**
   * @param int[] $ids
   */
  private function validateDynamicSelection(array $ids): void {
    foreach ($ids as $id) {
      $segment = $this->segmentsRepository->findOneById($id);
      if (!$segment instanceof SegmentEntity) {
        throw new ApiException(
          __('One or more selected dynamic segments were not found.', 'mailpoet'),
          400,
          'mailpoet_dynamic_segments_not_found'
        );
      }
      if ($segment->getType() !== SegmentEntity::TYPE_DYNAMIC) {
        throw new ApiException(
          __('This endpoint only supports dynamic segments.', 'mailpoet'),
          400,
          'mailpoet_dynamic_segments_invalid_type'
        );
      }
    }
  }

  /**
   * @param int[] $ids
   */
  private function validateDeletionSelection(array $ids): void {
    foreach ($ids as $id) {
      $segment = $this->segmentsRepository->findOneById($id);
      if ($segment instanceof SegmentEntity && $segment->getDeletedAt() === null) {
        throw new ApiException(
          __('Only dynamic segments in the trash can be permanently deleted.', 'mailpoet'),
          400,
          'mailpoet_dynamic_segments_delete_requires_trash'
        );
      }
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

<?php declare(strict_types = 1);

namespace MailPoet\Segments\RestApi\Endpoints;

use MailPoet\API\REST\ApiException;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Newsletter\Segment\NewsletterSegmentRepository;
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

  /** @var NewsletterSegmentRepository */
  private $newsletterSegmentRepository;

  public function __construct(
    SegmentsRepository $segmentsRepository,
    NewsletterSegmentRepository $newsletterSegmentRepository
  ) {
    $this->segmentsRepository = $segmentsRepository;
    $this->newsletterSegmentRepository = $newsletterSegmentRepository;
  }

  public function handle(Request $request): Response {
    $action = $this->validateAction($request);
    $this->validateGroup(is_string($request->getParam('group')) ? (string)$request->getParam('group') : null);
    $this->validateOrder(is_string($request->getParam('order')) ? (string)$request->getParam('order') : null, 'desc');
    $this->validatePage($request->getParam('page'));
    $this->validatePerPage($request->getParam('per_page'), 25);
    $orderby = is_string($request->getParam('orderby')) && $request->getParam('orderby') !== ''
      ? (string)$request->getParam('orderby')
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
    $ids = $this->validateIds($request->getParam('ids'));

    $result = [
      'updated' => 0,
      'deleted' => 0,
      'skipped' => 0,
      'errors' => [],
    ];

    if ($action === self::ACTION_TRASH) {
      $this->trashSegments($ids, $result);
    } elseif ($action === self::ACTION_RESTORE) {
      $result['updated'] = $this->segmentsRepository->bulkRestore($this->getDynamicIds($ids, $result), SegmentEntity::TYPE_DYNAMIC);
    } else {
      $result['deleted'] = $this->segmentsRepository->bulkDelete($this->getDynamicIds($ids, $result), SegmentEntity::TYPE_DYNAMIC);
    }

    return new Response($result);
  }

  public static function getRequestSchema(): array {
    return [
      'action' => Builder::string()->required(),
      'ids' => Builder::array(Builder::integer())->required(),
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
        'mailpoet_dynamic_segments_invalid_bulk_action'
      );
    }
    return $action;
  }

  /**
   * @param int[] $ids
   * @param array{updated:int,deleted:int,skipped:int,errors:array<int, array{id:int|null,message:string}>} $result
   */
  private function trashSegments(array $ids, array &$result): void {
    $newsletterBlockers = $this->newsletterSegmentRepository->getSubjectsOfActivelyUsedEmailsForSegments($ids);
    $trashIds = [];
    foreach ($this->getDynamicIds($ids, $result) as $id) {
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
   * @param array{updated:int,deleted:int,skipped:int,errors:array<int, array{id:int|null,message:string}>} $result
   * @return int[]
   */
  private function getDynamicIds(array $ids, array &$result): array {
    $dynamicIds = [];
    foreach ($ids as $id) {
      $segment = $this->segmentsRepository->findOneById($id);
      if (!$segment instanceof SegmentEntity) {
        $this->skip($result, $id, __('This segment does not exist.', 'mailpoet'));
        continue;
      }
      if ($segment->getType() !== SegmentEntity::TYPE_DYNAMIC) {
        $this->skip($result, $id, __('This segment action only supports dynamic segments.', 'mailpoet'));
        continue;
      }
      $dynamicIds[] = $id;
    }
    return $dynamicIds;
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

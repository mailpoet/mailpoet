<?php declare(strict_types = 1);

namespace MailPoet\CustomFields\RestApi\Endpoints;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\CustomFields\RestApi\CustomFieldApiException;
use MailPoet\Validator\Builder;

class CustomFieldsBulkActionEndpoint extends CustomFieldsEndpoint {
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

  /** @var CustomFieldsRepository */
  private $customFieldsRepository;

  public function __construct(
    CustomFieldsRepository $customFieldsRepository
  ) {
    $this->customFieldsRepository = $customFieldsRepository;
  }

  public function handle(Request $request): Response {
    $action = is_string($request->getParam('action')) ? (string)$request->getParam('action') : '';
    if (!in_array($action, self::SUPPORTED_ACTIONS, true)) {
      throw new CustomFieldApiException(
        // translators: %s is the list of supported bulk actions.
        sprintf(__('Unsupported bulk action. Allowed values are: %s.', 'mailpoet'), implode(', ', self::SUPPORTED_ACTIONS)),
        400,
        'mailpoet_custom_fields_invalid_bulk_action'
      );
    }

    $ids = $this->getIds($request, $action);
    $count = 0;
    if ($action === self::ACTION_TRASH) {
      $count = $this->customFieldsRepository->bulkTrash($ids);
    } elseif ($action === self::ACTION_RESTORE) {
      $count = $this->customFieldsRepository->bulkRestore($ids);
    } elseif ($action === self::ACTION_DELETE) {
      $count = $this->customFieldsRepository->bulkDelete($ids);
    } elseif ($action === self::ACTION_EMPTY_TRASH) {
      $count = $this->customFieldsRepository->emptyTrash();
    }

    return new Response([
      'action' => $action,
      'count' => $count,
    ]);
  }

  /**
   * @return int[]
   */
  private function getIds(Request $request, string $action): array {
    if ($action === self::ACTION_EMPTY_TRASH) {
      return [];
    }

    $rawIds = $request->getParam('ids');
    if (!is_array($rawIds) || $rawIds === []) {
      throw new CustomFieldApiException(
        __('At least one custom field id is required.', 'mailpoet'),
        400,
        'mailpoet_custom_fields_ids_required'
      );
    }

    $ids = array_values(array_filter(
      array_map(
        static function ($id): int {
          return is_int($id) || is_string($id) ? (int)$id : 0;
        },
        $rawIds
      ),
      static function (int $id): bool {
        return $id > 0;
      }
    ));
    if ($ids === []) {
      throw new CustomFieldApiException(
        __('At least one custom field id is required.', 'mailpoet'),
        400,
        'mailpoet_custom_fields_ids_required'
      );
    }
    return $ids;
  }

  public static function getRequestSchema(): array {
    return [
      'action' => Builder::string()->required(),
      'ids' => Builder::array(Builder::integer()),
    ];
  }
}

<?php declare(strict_types = 1);

namespace MailPoet\Form\RestApi\Endpoints;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Form\FormsRepository;
use MailPoet\Form\RestApi\FormApiException;
use MailPoet\Validator\Builder;

class FormsBulkActionEndpoint extends FormsEndpoint {
  private const ACTION_TRASH = 'trash';
  private const ACTION_RESTORE = 'restore';
  private const ACTION_DELETE = 'delete';

  private const SUPPORTED_ACTIONS = [
    self::ACTION_TRASH,
    self::ACTION_RESTORE,
    self::ACTION_DELETE,
  ];

  /** @var FormsRepository */
  private $formsRepository;

  public function __construct(
    FormsRepository $formsRepository
  ) {
    $this->formsRepository = $formsRepository;
  }

  public function handle(Request $request): Response {
    $action = is_string($request->getParam('action')) ? (string)$request->getParam('action') : '';
    if (!in_array($action, self::SUPPORTED_ACTIONS, true)) {
      throw new FormApiException(
        // translators: %s is the list of supported bulk actions.
        sprintf(__('Unsupported bulk action. Allowed values are: %s.', 'mailpoet'), implode(', ', self::SUPPORTED_ACTIONS)),
        400,
        'mailpoet_forms_invalid_bulk_action'
      );
    }

    $rawIds = $request->getParam('ids');
    if (!is_array($rawIds) || $rawIds === []) {
      throw new FormApiException(
        __('At least one form id is required.', 'mailpoet'),
        400,
        'mailpoet_forms_ids_required'
      );
    }

    $ids = array_values(array_filter(
      array_map('intval', $rawIds),
      static function (int $id): bool {
        return $id > 0;
      }
    ));
    if ($ids === []) {
      throw new FormApiException(
        __('At least one form id is required.', 'mailpoet'),
        400,
        'mailpoet_forms_ids_required'
      );
    }

    $count = 0;
    if ($action === self::ACTION_TRASH) {
      $count = $this->formsRepository->bulkTrash($ids);
    } elseif ($action === self::ACTION_RESTORE) {
      $count = $this->formsRepository->bulkRestore($ids);
    } elseif ($action === self::ACTION_DELETE) {
      $count = $this->formsRepository->bulkDelete($ids);
    }

    return new Response([
      'action' => $action,
      'count' => $count,
    ]);
  }

  public static function getRequestSchema(): array {
    return [
      'action' => Builder::string()->required(),
      'ids' => Builder::array(Builder::integer())->required(),
    ];
  }
}

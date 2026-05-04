<?php declare(strict_types = 1);

namespace MailPoet\CustomFields\RestApi\Endpoints;

use InvalidArgumentException;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\CustomFields\ApiDataSanitizer;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\CustomFields\RestApi\CustomFieldApiException;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Validator\Builder;
use MailPoetVendor\Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class CustomFieldsPutEndpoint extends CustomFieldsEndpoint {
  /** @var CustomFieldsRepository */
  private $customFieldsRepository;

  /** @var ApiDataSanitizer */
  private $apiDataSanitizer;

  public function __construct(
    CustomFieldsRepository $customFieldsRepository,
    ApiDataSanitizer $apiDataSanitizer
  ) {
    $this->customFieldsRepository = $customFieldsRepository;
    $this->apiDataSanitizer = $apiDataSanitizer;
  }

  public function handle(Request $request): Response {
    $id = $this->getId($request);
    $customField = $this->customFieldsRepository->findOneById($id);
    if (!$customField instanceof CustomFieldEntity || $customField->getDeletedAt() !== null) {
      throw new CustomFieldApiException(
        __('The custom field does not exist.', 'mailpoet'),
        404,
        'mailpoet_custom_fields_not_found'
      );
    }

    $requestData = $this->getRequestData($request);
    if (
      $requestData['type'] !== ''
      && $requestData['type'] !== $customField->getType()
      && $this->customFieldsRepository->hasSubscriberValues($id)
    ) {
      throw new CustomFieldApiException(
        __('The custom field type cannot be changed because subscribers have values stored for this field.', 'mailpoet'),
        409,
        'mailpoet_custom_fields_type_locked'
      );
    }

    try {
      $data = $this->apiDataSanitizer->sanitize($requestData);
    } catch (InvalidArgumentException $exception) {
      throw new CustomFieldApiException(
        $exception->getMessage(),
        400,
        'mailpoet_custom_fields_invalid_data',
        [],
        $exception
      );
    }

    $existing = $this->customFieldsRepository->findOneBy(['name' => $data['name']]);
    if ($existing instanceof CustomFieldEntity && $existing->getId() !== $id) {
      throw new CustomFieldApiException(
        __('A custom field with this name already exists.', 'mailpoet'),
        409,
        'mailpoet_custom_fields_duplicate'
      );
    }

    $data['id'] = $id;
    try {
      $customField = $this->customFieldsRepository->createOrUpdate($data);
    } catch (UniqueConstraintViolationException $exception) {
      // Concurrent request renamed another field to this name between the duplicate-name check and the update.
      throw new CustomFieldApiException(
        __('A custom field with this name already exists.', 'mailpoet'),
        409,
        'mailpoet_custom_fields_duplicate',
        [],
        $exception
      );
    }
    return new Response($this->buildItem($customField));
  }

  private function getId(Request $request): int {
    $rawId = $request->getParam('id');
    return (int)(is_scalar($rawId) ? $rawId : 0);
  }

  /**
   * @return array{name: string, type: string, params: array}
   */
  private function getRequestData(Request $request): array {
    $rawName = $request->getParam('name');
    $rawType = $request->getParam('type');
    $rawParams = $request->getParam('params');
    return [
      'name' => sanitize_text_field(is_scalar($rawName) ? (string)$rawName : ''),
      'type' => sanitize_key(is_scalar($rawType) ? (string)$rawType : ''),
      'params' => is_array($rawParams) ? $this->sanitizeParams($rawParams) : [],
    ];
  }

  private function sanitizeParams(array $params): array {
    $sanitized = [];
    foreach ($params as $key => $value) {
      if (is_array($value)) {
        $sanitized[$key] = $this->sanitizeParams($value);
      } elseif (is_scalar($value)) {
        $sanitized[$key] = sanitize_text_field((string)$value);
      }
    }
    return $sanitized;
  }

  public static function getRequestSchema(): array {
    return [
      'id' => Builder::integer()->required(),
      'name' => Builder::string()->required()->minLength(1),
      'type' => Builder::string()->required()->minLength(1),
      'params' => Builder::object(),
    ];
  }
}

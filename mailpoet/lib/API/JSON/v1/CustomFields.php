<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\Response;
use MailPoet\API\JSON\ResponseBuilders\CustomFieldsResponseBuilder;
use MailPoet\Config\AccessControl;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Form\ApiDataSanitizer;
use MailPoetVendor\Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class CustomFields extends APIEndpoint {
  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_FORMS,
    'methods' => [
      'delete' => AccessControl::PERMISSION_MANAGE_SUBSCRIBERS,
    ],
  ];

  /** @var CustomFieldsRepository */
  private $customFieldsRepository;

  /** @var CustomFieldsResponseBuilder */
  private $customFieldsResponseBuilder;

  /** @var ApiDataSanitizer */
  private $dataSanitizer;

  public function __construct(
    CustomFieldsRepository $customFieldsRepository,
    CustomFieldsResponseBuilder $customFieldsResponseBuilder,
    ApiDataSanitizer $dataSanitizer
  ) {
    $this->customFieldsRepository = $customFieldsRepository;
    $this->customFieldsResponseBuilder = $customFieldsResponseBuilder;
    $this->dataSanitizer = $dataSanitizer;
  }

  public function getAll() {
    $collection = $this->customFieldsRepository->findAllActive();
    return $this->successResponse($this->customFieldsResponseBuilder->buildBatch($collection));
  }

  public function delete($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : null);
    $customField = $this->customFieldsRepository->findOneById($id);
    if (!$customField instanceof CustomFieldEntity) {
      return $this->notFound();
    }

    $response = $this->customFieldsResponseBuilder->build($customField);
    $this->customFieldsRepository->deletePermanently((int)$customField->getId());
    return $this->successResponse($response);
  }

  public function save($data = []) {
    if (isset($data['type']) && is_string($data['type'])) {
      $data['type'] = strtolower($data['type']);
    }
    try {
      $data = $this->dataSanitizer->sanitizeBlock($data);
    } catch (\Exception $e) {
      return $this->errorResponse($errors = [], $meta = [], $status = Response::STATUS_BAD_REQUEST);
    }

    $id = isset($data['id']) ? (int)$data['id'] : null;
    if ($id) {
      $customField = $this->customFieldsRepository->findOneById($id);
      if (!$customField instanceof CustomFieldEntity || $customField->getDeletedAt() !== null) {
        return $this->notFound();
      }
      if (
        isset($data['type'])
        && $data['type'] !== $customField->getType()
        && $this->customFieldsRepository->hasSubscriberValues($id)
      ) {
        return $this->conflict(__('The custom field type cannot be changed because subscribers have values stored for this field.', 'mailpoet'));
      }
    }

    if (isset($data['name'])) {
      $existing = $this->customFieldsRepository->findOneBy(['name' => $data['name']]);
      if ($existing instanceof CustomFieldEntity && $existing->getId() !== $id) {
        return $this->conflict(__('A custom field with this name already exists.', 'mailpoet'));
      }
    }

    try {
      $customField = $this->customFieldsRepository->createOrUpdate($data);
      $customField = $this->customFieldsRepository->findOneById($customField->getId());
      if(!$customField instanceof CustomFieldEntity) return $this->errorResponse();
      return $this->successResponse($this->customFieldsResponseBuilder->build($customField));
    } catch (UniqueConstraintViolationException $e) {
      return $this->conflict(__('A custom field with this name already exists.', 'mailpoet'));
    } catch (\Exception $e) {
      return $this->errorResponse($errors = [], $meta = [], $status = Response::STATUS_BAD_REQUEST);
    }
  }

  public function get($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : null);
    $customField = $this->customFieldsRepository->findOneById($id);
    if ($customField instanceof CustomFieldEntity) {
      return $this->successResponse($this->customFieldsResponseBuilder->build($customField));
    }
    return $this->notFound();
  }

  private function notFound(): Response {
    return $this->errorResponse([
      APIError::NOT_FOUND => __('This custom field does not exist.', 'mailpoet'),
    ]);
  }

  private function conflict(string $message): Response {
    return $this->errorResponse([
      APIError::CONFLICT => $message,
    ], [], Response::STATUS_CONFLICT);
  }
}

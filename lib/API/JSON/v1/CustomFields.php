<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\Response;
use MailPoet\API\JSON\ResponseBuilders\CustomFieldsResponseBuilder;
use MailPoet\Config\AccessControl;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Models\CustomField;
use MailPoet\WP\Functions as WPFunctions;

class CustomFields extends APIEndpoint {
  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_FORMS,
  ];

  /** @var CustomFieldsRepository */
  private $custom_fields_repository;

  /** @var CustomFieldsResponseBuilder */
  private $custom_fields_response_builder;

  public function __construct(
    CustomFieldsRepository $custom_fields_repository,
    CustomFieldsResponseBuilder $custom_fields_response_builder
  ) {
    $this->custom_fields_repository = $custom_fields_repository;
    $this->custom_fields_response_builder = $custom_fields_response_builder;
  }

  function getAll() {
    $collection = $this->custom_fields_repository->findBy([], ['created_at' => 'asc']);
    return $this->successResponse($this->custom_fields_response_builder->buildBatch($collection));
  }

  function delete($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : null);
    $custom_field = $this->custom_fields_repository->findOneById($id);
    if ($custom_field instanceof CustomFieldEntity) {
      $this->custom_fields_repository->remove($custom_field);
      $this->custom_fields_repository->flush();

      return $this->successResponse($this->custom_fields_response_builder->build($custom_field));
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This custom field does not exist.', 'mailpoet'),
      ]);
    }
  }

  function save($data = []) {
    try {
      $custom_field = $this->custom_fields_repository->createOrUpdate($data);
      $custom_field = $this->custom_fields_repository->findOneById($custom_field->getId());
      if(!$custom_field instanceof CustomFieldEntity) return $this->errorResponse();
      return $this->successResponse($this->custom_fields_response_builder->build($custom_field));
    } catch (\Exception $e) {
      return $this->errorResponse($errors = [], $meta = [], $status = Response::STATUS_BAD_REQUEST);
    }
  }

  function get($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : null);
    $custom_field = $this->custom_fields_repository->findOneById($id);
    if ($custom_field instanceof CustomFieldEntity) {
      return $this->successResponse($this->custom_fields_response_builder->build($custom_field));
    }
    return $this->errorResponse([
      APIError::NOT_FOUND => WPFunctions::get()->__('This custom field does not exist.', 'mailpoet'),
    ]);
  }
}

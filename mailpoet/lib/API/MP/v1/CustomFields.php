<?php declare(strict_types = 1);

namespace MailPoet\API\MP\v1;

use MailPoet\CustomFields\ApiDataSanitizer;
use MailPoet\CustomFields\CustomFieldsRepository;

class CustomFields {
  /** @var ApiDataSanitizer */
  private $customFieldsDataSanitizer;

  /** @var CustomFieldsRepository */
  private $customFieldsRepository;

  public function __construct(
    ApiDataSanitizer $customFieldsDataSanitizer,
    CustomFieldsRepository $customFieldsRepository
  ) {
    $this->customFieldsDataSanitizer = $customFieldsDataSanitizer;
    $this->customFieldsRepository = $customFieldsRepository;
  }

  public function getSubscriberFields(): array {
    $data = [
      [
        'id' => 'email',
        'name' => __('Email', 'mailpoet'),
        'type' => 'text',
        'params' => [
          'required' => '1',
        ],
      ],
      [
        'id' => 'first_name',
        'name' => __('First name', 'mailpoet'),
        'type' => 'text',
        'params' => [
          'required' => '',
        ],
      ],
      [
        'id' => 'last_name',
        'name' => __('Last name', 'mailpoet'),
        'type' => 'text',
        'params' => [
          'required' => '',
        ],
      ],
    ];

    $customFields = $this->customFieldsRepository->findAllActive();
    foreach ($customFields as $customField) {
      $result = [
        'id' => 'cf_' . $customField->getId(),
        'name' => $customField->getName(),
        'type' => $customField->getType(),
        'params' => $customField->getParams(),
      ];
      $data[] = $result;
    }

    return $data;
  }

  public function addSubscriberField(array $data = []): array {
    // Run sanitize() OUTSIDE the try/catch so its InvalidArgumentException
    // propagates to API::addSubscriberField, which maps it to an APIException
    // carrying the original sanitizer code (1001-1010). Wrapping it here would
    // collapse every validation error into FAILED_TO_SAVE_SUBSCRIBER_FIELD (1)
    // and make the documented sanitizer codes unreachable.
    $sanitized = $this->customFieldsDataSanitizer->sanitize($data);
    try {
      $customField = $this->customFieldsRepository->createOrUpdate($sanitized);
    } catch (\Exception $e) {
      throw new APIException('Failed to save a new subscriber field ' . $e->getMessage(), APIException::FAILED_TO_SAVE_SUBSCRIBER_FIELD);
    }
    return [
      'id' => 'cf_' . $customField->getId(),
      'name' => $customField->getName(),
      'type' => $customField->getType(),
      'params' => $customField->getParams(),
    ];
  }
}

<?php declare(strict_types = 1);

namespace MailPoet\CustomFields\RestApi\Endpoints;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\CustomFields\RestApi\CustomFieldApiException;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Validator\Builder;
use MailPoetVendor\Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class CustomFieldsDuplicateEndpoint extends CustomFieldsEndpoint {
  /** @var CustomFieldsRepository */
  private $customFieldsRepository;

  public function __construct(
    CustomFieldsRepository $customFieldsRepository
  ) {
    $this->customFieldsRepository = $customFieldsRepository;
  }

  public function handle(Request $request): Response {
    $customField = $this->customFieldsRepository->findOneById($this->getId($request));
    if (!$customField instanceof CustomFieldEntity || $customField->getDeletedAt() !== null) {
      throw new CustomFieldApiException(
        __('The custom field does not exist.', 'mailpoet'),
        404,
        'mailpoet_custom_fields_not_found'
      );
    }

    $params = $customField->getParams() ?: [];
    $params['label'] = $this->getDuplicateLabel($params, $customField->getName());

    $attempts = 0;
    while (true) {
      try {
        $duplicate = $this->customFieldsRepository->createOrUpdate([
          'name' => $this->getDuplicateName($customField->getName()),
          'type' => $customField->getType(),
          'params' => $params,
        ]);
        break;
      } catch (UniqueConstraintViolationException $exception) {
        // A concurrent duplicate request picked the same candidate name. Recompute and retry.
        if (++$attempts >= 3) {
          throw new CustomFieldApiException(
            __('A custom field with this name already exists.', 'mailpoet'),
            409,
            'mailpoet_custom_fields_duplicate',
            [],
            $exception
          );
        }
      }
    }

    return new Response($this->buildItem($duplicate), 201);
  }

  private function getId(Request $request): int {
    $rawId = $request->getParam('id');
    return (int)(is_scalar($rawId) ? $rawId : 0);
  }

  private function getDuplicateName(string $name): string {
    /* translators: %s is the original custom field name. */
    $candidate = sprintf(__('%s copy', 'mailpoet'), $name);
    $suffix = 2;
    while ($this->customFieldsRepository->findOneBy(['name' => $candidate]) instanceof CustomFieldEntity) {
      /* translators: %1$s is the original custom field name, %2$d is the copy number. */
      $candidate = sprintf(__('%1$s copy %2$d', 'mailpoet'), $name, $suffix);
      $suffix++;
    }
    return $candidate;
  }

  private function getDuplicateLabel(array $params, string $fallback): string {
    $label = isset($params['label']) && is_scalar($params['label']) ? (string)$params['label'] : $fallback;
    /* translators: %s is the original custom field label. */
    return sprintf(__('%s copy', 'mailpoet'), $label);
  }

  public static function getRequestSchema(): array {
    return [
      'id' => Builder::integer()->required(),
    ];
  }
}

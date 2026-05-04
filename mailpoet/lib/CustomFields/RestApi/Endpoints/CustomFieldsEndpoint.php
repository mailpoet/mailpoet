<?php declare(strict_types = 1);

namespace MailPoet\CustomFields\RestApi\Endpoints;

use MailPoet\API\REST\Endpoint;
use MailPoet\Config\AccessControl;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\WP\Functions as WPFunctions;

abstract class CustomFieldsEndpoint extends Endpoint {
  private const DATE_FORMAT = 'Y-m-d H:i:s';

  public function checkPermissions(): bool {
    return WPFunctions::get()->currentUserCan(AccessControl::PERMISSION_MANAGE_SUBSCRIBERS);
  }

  protected function buildItem(CustomFieldEntity $customField): array {
    $params = $customField->getParams();
    $label = isset($params['label']) && is_scalar($params['label']) ? (string)$params['label'] : $customField->getName();
    return [
      'id' => (int)$customField->getId(),
      'name' => $customField->getName(),
      'label' => $label,
      'type' => $customField->getType(),
      'params' => $params,
      'subscribers_count' => 0,
      'forms_count' => 0,
      'dynamic_segments_count' => 0,
      'created_at' => ($createdAt = $customField->getCreatedAt()) ? $createdAt->format(self::DATE_FORMAT) : null,
      'updated_at' => ($updatedAt = $customField->getUpdatedAt()) ? $updatedAt->format(self::DATE_FORMAT) : null,
      'deleted_at' => ($deletedAt = $customField->getDeletedAt()) ? $deletedAt->format(self::DATE_FORMAT) : null,
    ];
  }

  /**
   * @param array{id: int, name: string, label: string, type: string, params: array, subscribers_count: int, forms_count: int, dynamic_segments_count: int, created_at: ?\DateTimeInterface, updated_at: ?\DateTimeInterface, deleted_at: ?\DateTimeInterface} $row
   */
  protected function buildItemFromRow(array $row): array {
    return [
      'id' => (int)$row['id'],
      'name' => (string)$row['name'],
      'label' => (string)$row['label'],
      'type' => (string)$row['type'],
      'params' => $row['params'],
      'subscribers_count' => (int)$row['subscribers_count'],
      'forms_count' => (int)$row['forms_count'],
      'dynamic_segments_count' => (int)$row['dynamic_segments_count'],
      'created_at' => $row['created_at'] instanceof \DateTimeInterface ? $row['created_at']->format(self::DATE_FORMAT) : null,
      'updated_at' => $row['updated_at'] instanceof \DateTimeInterface ? $row['updated_at']->format(self::DATE_FORMAT) : null,
      'deleted_at' => $row['deleted_at'] instanceof \DateTimeInterface ? $row['deleted_at']->format(self::DATE_FORMAT) : null,
    ];
  }
}

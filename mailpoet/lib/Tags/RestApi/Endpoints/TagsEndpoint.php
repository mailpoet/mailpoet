<?php declare(strict_types = 1);

namespace MailPoet\Tags\RestApi\Endpoints;

use MailPoet\API\REST\Endpoint;
use MailPoet\Config\AccessControl;
use MailPoet\Entities\TagEntity;
use MailPoet\WP\Functions as WPFunctions;

abstract class TagsEndpoint extends Endpoint {
  private const DATE_FORMAT = 'Y-m-d H:i:s';

  public function checkPermissions(): bool {
    return WPFunctions::get()->currentUserCan(AccessControl::PERMISSION_MANAGE_SUBSCRIBERS);
  }

  protected function buildItem(TagEntity $tag, int $subscribersCount = 0): array {
    return [
      'id' => (int)$tag->getId(),
      'name' => $tag->getName(),
      'description' => $tag->getDescription(),
      'subscribers_count' => $subscribersCount,
      'created_at' => ($createdAt = $tag->getCreatedAt()) ? $createdAt->format(self::DATE_FORMAT) : null,
      'updated_at' => ($updatedAt = $tag->getUpdatedAt()) ? $updatedAt->format(self::DATE_FORMAT) : null,
    ];
  }

  /**
   * @param array{id: int, name: string, description: string, subscribers_count: int, created_at: ?\DateTimeInterface, updated_at: ?\DateTimeInterface} $row
   */
  protected function buildItemFromRow(array $row): array {
    return [
      'id' => (int)$row['id'],
      'name' => (string)$row['name'],
      'description' => (string)$row['description'],
      'subscribers_count' => (int)$row['subscribers_count'],
      'created_at' => $row['created_at'] instanceof \DateTimeInterface ? $row['created_at']->format(self::DATE_FORMAT) : null,
      'updated_at' => $row['updated_at'] instanceof \DateTimeInterface ? $row['updated_at']->format(self::DATE_FORMAT) : null,
    ];
  }
}

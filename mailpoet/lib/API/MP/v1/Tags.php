<?php declare(strict_types = 1);

namespace MailPoet\API\MP\v1;

use MailPoet\Entities\TagEntity;
use MailPoet\Tags\TagRepository;

class Tags {
  private const DATE_FORMAT = 'Y-m-d H:i:s';

  /** @var TagRepository */
  private $tagRepository;

  public function __construct(
    TagRepository $tagRepository
  ) {
    $this->tagRepository = $tagRepository;
  }

  public function getAll(): array {
    $tags = $this->tagRepository->findBy([], ['id' => 'asc']);
    $result = [];
    foreach ($tags as $tag) {
      $result[] = $this->buildItem($tag);
    }
    return $result;
  }

  /**
   * @param int|string $tagIdOrName
   * @throws APIException
   */
  public function getTag($tagIdOrName): array {
    $tag = $this->findTag($tagIdOrName);
    return $this->buildItem($tag);
  }

  public function addTag(array $data): array {
    $this->validateTagName($data);

    try {
      $name = isset($data['name']) ? sanitize_text_field($data['name']) : '';
      $description = isset($data['description']) ? sanitize_text_field($data['description']) : '';
      $tag = new TagEntity($name, $description);
      $this->tagRepository->persist($tag);
      $this->tagRepository->flush();
    } catch (\Exception $e) {
      throw new APIException(
        __('The tag couldn’t be created in the database', 'mailpoet'),
        APIException::FAILED_TO_SAVE_TAG
      );
    }

    return $this->buildItem($tag);
  }

  public function updateTag(array $data): array {
    $this->validateTagId((string)($data['id'] ?? ''));
    $this->validateTagName($data);

    $tag = $this->tagRepository->findOneById((int)$data['id']);
    if (!$tag instanceof TagEntity) {
      throw new APIException(
        __('The tag does not exist.', 'mailpoet'),
        APIException::TAG_NOT_EXISTS
      );
    }

    $name = isset($data['name']) ? sanitize_text_field($data['name']) : '';
    $description = isset($data['description']) ? sanitize_text_field($data['description']) : '';

    try {
      $tag->setName($name);
      $tag->setDescription($description);
      $this->tagRepository->flush();
    } catch (\Exception $e) {
      throw new APIException(
        __('The tag couldn’t be updated in the database', 'mailpoet'),
        APIException::FAILED_TO_UPDATE_TAG
      );
    }

    return $this->buildItem($tag);
  }

  public function deleteTag(string $tagId): bool {
    $this->validateTagId($tagId);

    $tag = $this->tagRepository->findOneById((int)$tagId);
    if (!$tag instanceof TagEntity) {
      throw new APIException(
        __('The tag does not exist.', 'mailpoet'),
        APIException::TAG_NOT_EXISTS
      );
    }

    try {
      $this->tagRepository->deleteTag($tag);
      return true;
    } catch (\Exception $e) {
      throw new APIException(
        __('The tag couldn’t be deleted from the database', 'mailpoet'),
        APIException::FAILED_TO_DELETE_TAG
      );
    }
  }

  /**
   * @param int|string $tagIdOrName
   * @throws APIException
   */
  private function findTag($tagIdOrName): TagEntity {
    $tag = null;
    if (is_int($tagIdOrName) || (is_string($tagIdOrName) && (string)(int)$tagIdOrName === $tagIdOrName)) {
      $tag = $this->tagRepository->findOneById((int)$tagIdOrName);
    } elseif (is_string($tagIdOrName) && strlen(trim($tagIdOrName)) > 0) {
      $tag = $this->tagRepository->findOneBy(['name' => $tagIdOrName]);
    }

    if (!$tag instanceof TagEntity) {
      throw new APIException(
        __('The tag does not exist.', 'mailpoet'),
        APIException::TAG_NOT_EXISTS
      );
    }

    return $tag;
  }

  private function validateTagId(string $tagId): void {
    if ($tagId === '') {
      throw new APIException(
        __('Tag id is required.', 'mailpoet'),
        APIException::TAG_ID_REQUIRED
      );
    }

    if (!$this->tagRepository->findOneById((int)$tagId)) {
      throw new APIException(
        __('The tag does not exist.', 'mailpoet'),
        APIException::TAG_NOT_EXISTS
      );
    }
  }

  private function validateTagName(array $data): void {
    if (empty($data['name'])) {
      throw new APIException(
        __('Tag name is required.', 'mailpoet'),
        APIException::TAG_NAME_REQUIRED
      );
    }

    $tagId = isset($data['id']) ? (int)$data['id'] : null;
    $existing = $this->tagRepository->findOneBy(['name' => $data['name']]);
    if ($existing instanceof TagEntity && $existing->getId() !== $tagId) {
      throw new APIException(
        __('This tag already exists.', 'mailpoet'),
        APIException::TAG_EXISTS
      );
    }
  }

  private function buildItem(TagEntity $tag): array {
    return [
      'id' => (string)$tag->getId(),
      'name' => $tag->getName(),
      'description' => $tag->getDescription(),
      'created_at' => ($createdAt = $tag->getCreatedAt()) ? $createdAt->format(self::DATE_FORMAT) : null,
      'updated_at' => ($updatedAt = $tag->getUpdatedAt()) ? $updatedAt->format(self::DATE_FORMAT) : null,
    ];
  }
}

<?php declare(strict_types = 1);

namespace MailPoet\Tags;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\TagEntity;

/**
 * @extends Repository<TagEntity>
 */
class TagRepository extends Repository {
  protected function getEntityClassName() {
    return TagEntity::class;
  }

  public function createOrUpdate(array $data = []): TagEntity {
    if (!$data['name']) {
      throw new \InvalidArgumentException('Missing name');
    }
    $tag = $this->findOneBy([
      'name' => $data['name'],
    ]);
    if (!$tag) {
      $tag = new TagEntity($data['name']);
      $this->persist($tag);
    }

    try {
      $this->flush();
    } catch (\Exception $e) {
      throw new \RuntimeException("Error when saving tag " . $data['name']);
    }
    return $tag;
  }
}

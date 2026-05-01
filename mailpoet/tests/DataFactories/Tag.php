<?php declare(strict_types = 1);

namespace MailPoet\Test\DataFactories;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\TagEntity;
use MailPoet\Tags\TagRepository;

class Tag {
  /** @var array */
  private $data;

  /** @var TagRepository */
  private $tagRepository;

  public function __construct() {
    $this->tagRepository = ContainerWrapper::getInstance()->get(TagRepository::class);
    $this->data = [
      'name' => 'Tag' . bin2hex(random_bytes(7)),
    ];
  }

  /**
   * @return static
   */
  public function withName(string $name) {
    return $this->update('name', $name);
  }

  /**
   * @return static
   */
  public function withDescription(string $description) {
    return $this->update('description', $description);
  }

  public function create(): TagEntity {
    $tag = new TagEntity($this->data['name'], $this->data['description'] ?? '');
    $this->tagRepository->persist($tag);
    $this->tagRepository->flush();
    return $tag;
  }

  /**
   * @return static
   */
  private function update(string $item, $value) {
    $data = $this->data;
    $data[$item] = $value;
    $new = clone $this;
    $new->data = $data;
    return $new;
  }
}

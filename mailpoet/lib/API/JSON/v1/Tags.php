<?php declare(strict_types = 1);

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\Entities\TagEntity;
use MailPoet\InvalidStateException;
use MailPoet\Tags\TagRepository;

class Tags extends APIEndpoint {

  private $repository;

  public function __construct(
    TagRepository $repository
  ) {
    $this->repository = $repository;
  }

  public function save($data = []) {
    if (!isset($data['name'])) {
      throw InvalidStateException::create()->withError(
        'tag_without_name',
        __('A tag needs to have a name.', 'mailpoet')
      );
    }

    $data['name'] = sanitize_text_field(wp_unslash($data['name']));
    $data['description'] = isset($data['description']) ? sanitize_text_field(wp_unslash($data['description'])) : '';

    return $this->successResponse(
      $this->mapTagEntity($this->repository->createOrUpdate($data))
    );
  }

  public function listing() {
    return $this->successResponse(
      array_map(
        [$this, 'mapTagEntity'],
        $this->repository->findAll()
      )
    );
  }

  private function mapTagEntity(TagEntity $tag): array {
    return [
      'id' => $tag->getId(),
      'name' => $tag->getName(),
      'description' => $tag->getDescription(),
      'created' => $tag->getCreatedAt(),
      'updated' => $tag->getUpdatedAt(),
    ];
  }
}

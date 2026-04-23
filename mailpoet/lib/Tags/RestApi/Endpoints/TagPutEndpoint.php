<?php declare(strict_types = 1);

namespace MailPoet\Tags\RestApi\Endpoints;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Entities\TagEntity;
use MailPoet\Tags\RestApi\TagApiException;
use MailPoet\Tags\TagRepository;
use MailPoet\Validator\Builder;

class TagPutEndpoint extends TagsEndpoint {
  /** @var TagRepository */
  private $tagRepository;

  public function __construct(
    TagRepository $tagRepository
  ) {
    $this->tagRepository = $tagRepository;
  }

  public function handle(Request $request): Response {
    /** @var mixed $rawId */
    $rawId = $request->getParam('id');
    $id = (int)(is_scalar($rawId) ? $rawId : 0);
    $tag = $this->tagRepository->findOneById($id);
    if (!$tag instanceof TagEntity) {
      throw new TagApiException(
        __('The tag does not exist.', 'mailpoet'),
        404,
        'mailpoet_tags_not_found'
      );
    }

    $params = $request->getParams();
    $hasName = array_key_exists('name', $params);
    $hasDescription = array_key_exists('description', $params);

    if ($hasName) {
      $name = sanitize_text_field((string)$params['name']);
      if ($name === '') {
        throw new TagApiException(
          __('Tag name is required.', 'mailpoet'),
          400,
          'mailpoet_tags_name_required'
        );
      }
      $existing = $this->tagRepository->findOneBy(['name' => $name]);
      if ($existing instanceof TagEntity && $existing->getId() !== $id) {
        throw new TagApiException(
          __('A tag with this name already exists.', 'mailpoet'),
          409,
          'mailpoet_tags_duplicate'
        );
      }
      $tag->setName($name);
    }

    if ($hasDescription) {
      $tag->setDescription(sanitize_textarea_field((string)$params['description']));
    }

    $this->tagRepository->flush();

    return new Response($this->buildItem($tag, $this->tagRepository->getSubscribersCount($id)));
  }

  public static function getRequestSchema(): array {
    return [
      'id' => Builder::integer()->required(),
      'name' => Builder::string()->minLength(1),
      'description' => Builder::string(),
    ];
  }
}

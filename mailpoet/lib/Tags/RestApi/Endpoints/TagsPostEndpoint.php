<?php declare(strict_types = 1);

namespace MailPoet\Tags\RestApi\Endpoints;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Entities\TagEntity;
use MailPoet\Tags\RestApi\TagApiException;
use MailPoet\Tags\TagRepository;
use MailPoet\Validator\Builder;

class TagsPostEndpoint extends TagsEndpoint {
  /** @var TagRepository */
  private $tagRepository;

  public function __construct(
    TagRepository $tagRepository
  ) {
    $this->tagRepository = $tagRepository;
  }

  public function handle(Request $request): Response {
    /** @var mixed $rawName */
    $rawName = $request->getParam('name');
    /** @var mixed $rawDescription */
    $rawDescription = $request->getParam('description');
    $name = sanitize_text_field(is_scalar($rawName) ? (string)$rawName : '');
    $description = sanitize_textarea_field(is_scalar($rawDescription) ? (string)$rawDescription : '');

    if ($name === '') {
      throw new TagApiException(
        __('Tag name is required.', 'mailpoet'),
        400,
        'mailpoet_tags_name_required'
      );
    }

    $existing = $this->tagRepository->findOneBy(['name' => $name]);
    if ($existing instanceof TagEntity) {
      throw new TagApiException(
        __('A tag with this name already exists.', 'mailpoet'),
        409,
        'mailpoet_tags_duplicate'
      );
    }

    $tag = new TagEntity($name, $description);
    $this->tagRepository->persist($tag);
    $this->tagRepository->flush();

    return new Response($this->buildItem($tag, 0), 201);
  }

  public static function getRequestSchema(): array {
    return [
      'name' => Builder::string()->required()->minLength(1),
      'description' => Builder::string(),
    ];
  }
}

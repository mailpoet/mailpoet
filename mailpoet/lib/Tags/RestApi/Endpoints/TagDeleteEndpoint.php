<?php declare(strict_types = 1);

namespace MailPoet\Tags\RestApi\Endpoints;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Entities\TagEntity;
use MailPoet\Tags\RestApi\TagApiException;
use MailPoet\Tags\TagRepository;
use MailPoet\Validator\Builder;

class TagDeleteEndpoint extends TagsEndpoint {
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

    $this->tagRepository->deleteTag($tag);
    return new Response(null);
  }

  public static function getRequestSchema(): array {
    return [
      'id' => Builder::integer()->required(),
    ];
  }
}

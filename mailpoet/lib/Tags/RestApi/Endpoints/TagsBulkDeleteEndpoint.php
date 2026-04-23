<?php declare(strict_types = 1);

namespace MailPoet\Tags\RestApi\Endpoints;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Tags\RestApi\TagApiException;
use MailPoet\Tags\TagRepository;
use MailPoet\Validator\Builder;

class TagsBulkDeleteEndpoint extends TagsEndpoint {
  /** @var TagRepository */
  private $tagRepository;

  public function __construct(
    TagRepository $tagRepository
  ) {
    $this->tagRepository = $tagRepository;
  }

  public function handle(Request $request): Response {
    $rawIds = $request->getParam('ids');
    if (!is_array($rawIds) || $rawIds === []) {
      throw new TagApiException(
        __('At least one tag id is required.', 'mailpoet'),
        400,
        'mailpoet_tags_ids_required'
      );
    }

    $ids = array_values(array_filter(array_map('intval', $rawIds)));
    $deleted = $this->tagRepository->bulkDelete($ids);

    return new Response(['deleted' => $deleted]);
  }

  public static function getRequestSchema(): array {
    return [
      'ids' => Builder::array(Builder::integer())->required(),
    ];
  }
}

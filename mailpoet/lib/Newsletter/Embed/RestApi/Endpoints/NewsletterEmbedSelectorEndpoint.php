<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Embed\RestApi\Endpoints;

use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Newsletter\Embed\NewsletterEmbedService;
use MailPoet\Validator\Builder;

class NewsletterEmbedSelectorEndpoint extends NewsletterEmbedEndpoint {
  /** @var NewsletterEmbedService */
  private $newsletterEmbedService;

  public function __construct(
    NewsletterEmbedService $newsletterEmbedService
  ) {
    $this->newsletterEmbedService = $newsletterEmbedService;
  }

  public function handle(Request $request): Response {
    $search = is_string($request->getParam('search')) ? (string)$request->getParam('search') : '';
    $limit = is_numeric($request->getParam('limit')) ? (int)$request->getParam('limit') : null;

    return new Response([
      'items' => $this->newsletterEmbedService->getSelectorItems($search, $limit),
    ]);
  }

  public static function getRequestSchema(): array {
    return [
      'search' => Builder::string(),
      'limit' => Builder::integer(),
    ];
  }
}

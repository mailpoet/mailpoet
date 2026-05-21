<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\RestApi\Endpoints;

use MailPoet\API\JSON\ResponseBuilders\NewslettersResponseBuilder;
use MailPoet\API\REST\ApiException;
use MailPoet\API\REST\Endpoint;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Config\AccessControl;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewsletterSaveController;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Validator\Builder;
use MailPoet\WP\Functions as WPFunctions;

/**
 * `POST /mailpoet/v1/newsletters/{id}/duplicate`
 *
 * Returns the freshly-created copy in the same shape `NewslettersResponseBuilder::build()`
 * uses elsewhere so the listing can splice it in without a refresh round-trip.
 */
class NewsletterDuplicateEndpoint extends Endpoint {
  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var NewsletterSaveController */
  private $newsletterSaveController;

  /** @var NewslettersResponseBuilder */
  private $newslettersResponseBuilder;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    NewslettersRepository $newslettersRepository,
    NewsletterSaveController $newsletterSaveController,
    NewslettersResponseBuilder $newslettersResponseBuilder,
    WPFunctions $wp
  ) {
    $this->newslettersRepository = $newslettersRepository;
    $this->newsletterSaveController = $newsletterSaveController;
    $this->newslettersResponseBuilder = $newslettersResponseBuilder;
    $this->wp = $wp;
  }

  public function checkPermissions(): bool {
    return $this->wp->currentUserCan(AccessControl::PERMISSION_MANAGE_EMAILS);
  }

  public function handle(Request $request): Response {
    $idParam = $request->getParam('id');
    $id = is_numeric($idParam) ? (int)$idParam : 0;
    $newsletter = $this->newslettersRepository->findOneById($id);
    if (!$newsletter instanceof NewsletterEntity) {
      throw new ApiException(
        __('This email does not exist.', 'mailpoet'),
        404,
        'mailpoet_newsletters_not_found'
      );
    }

    $duplicate = $this->newsletterSaveController->duplicate($newsletter);
    $this->wp->doAction('mailpoet_api_newsletters_duplicate_after', $newsletter, $duplicate);

    return new Response($this->newslettersResponseBuilder->build($duplicate));
  }

  public static function getRequestSchema(): array {
    return [
      'id' => Builder::integer()->required(),
    ];
  }
}

<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\RestApi\Endpoints;

use MailPoet\API\JSON\ResponseBuilders\NewslettersResponseBuilder;
use MailPoet\API\REST\ApiException;
use MailPoet\API\REST\Endpoint;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Config\AccessControl;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\BulkActionException;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\StatusController;
use MailPoet\Validator\Builder;
use MailPoet\WP\Functions as WPFunctions;

/**
 * `PUT /mailpoet/v1/newsletters/{id}/status`
 *
 * Replaces the legacy `setStatus` JSON action. Delegates to
 * {@see StatusController} so the active/draft toggle, paused-task resume,
 * and post-notification reschedule logic stay in one place.
 */
class NewsletterStatusEndpoint extends Endpoint {
  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var StatusController */
  private $statusController;

  /** @var NewslettersResponseBuilder */
  private $newslettersResponseBuilder;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    NewslettersRepository $newslettersRepository,
    StatusController $statusController,
    NewslettersResponseBuilder $newslettersResponseBuilder,
    WPFunctions $wp
  ) {
    $this->newslettersRepository = $newslettersRepository;
    $this->statusController = $statusController;
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

    $statusParam = $request->getParam('status');
    if (!is_string($statusParam) || $statusParam === '') {
      throw new ApiException(
        __('You need to specify a status.', 'mailpoet'),
        400,
        'mailpoet_newsletters_missing_status'
      );
    }

    try {
      $updated = $this->statusController->setStatus($newsletter, $statusParam);
    } catch (BulkActionException $exception) {
      throw new ApiException(
        $exception->getMessage(),
        $exception->getStatusCode(),
        $exception->getErrorCode()
      );
    }

    return new Response($this->newslettersResponseBuilder->build($updated));
  }

  public static function getRequestSchema(): array {
    return [
      'id' => Builder::integer()->required(),
      'status' => Builder::string()->required(),
    ];
  }
}

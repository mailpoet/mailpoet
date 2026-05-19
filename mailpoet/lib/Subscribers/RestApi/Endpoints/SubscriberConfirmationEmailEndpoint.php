<?php declare(strict_types = 1);

namespace MailPoet\Subscribers\RestApi\Endpoints;

use MailPoet\API\REST\ApiException;
use MailPoet\API\REST\Endpoint;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Config\AccessControl;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Validator\Builder;
use MailPoet\WP\Functions as WPFunctions;

/**
 * `POST /mailpoet/v1/subscribers/{id}/resend-confirmation-email`
 *
 * Replaces the legacy `MailPoet\API\JSON\v1\Subscribers::sendConfirmationEmail`
 * call. Per-list confirmation settings are intentionally not resolved here; the
 * global default is used to avoid ambiguity across multiple segments.
 */
class SubscriberConfirmationEmailEndpoint extends Endpoint {
  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var ConfirmationEmailMailer */
  private $confirmationEmailMailer;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    SubscribersRepository $subscribersRepository,
    ConfirmationEmailMailer $confirmationEmailMailer,
    WPFunctions $wp
  ) {
    $this->subscribersRepository = $subscribersRepository;
    $this->confirmationEmailMailer = $confirmationEmailMailer;
    $this->wp = $wp;
  }

  public function checkPermissions(): bool {
    return $this->wp->currentUserCan(AccessControl::PERMISSION_MANAGE_SUBSCRIBERS);
  }

  public function handle(Request $request): Response {
    $idParam = $request->getParam('id');
    $id = is_numeric($idParam) ? (int)$idParam : 0;
    $subscriber = $this->subscribersRepository->findOneById($id);
    if (!$subscriber instanceof SubscriberEntity) {
      throw new ApiException(
        __('This subscriber does not exist.', 'mailpoet'),
        404,
        'mailpoet_subscribers_not_found'
      );
    }

    try {
      $result = $this->confirmationEmailMailer->sendAdminConfirmationEmail($subscriber);
    } catch (\Exception $exception) {
      throw new ApiException(
        __('There was a problem with your sending method. Please check if your sending method is properly configured.', 'mailpoet'),
        500,
        'mailpoet_subscribers_sending_failed',
        [],
        $exception
      );
    }

    if (($result['status'] ?? null) === 'sent') {
      return new Response(['sent' => true]);
    }

    $reason = $result['reason'] ?? null;
    if ($reason === 'max_confirmations_reached') {
      throw new ApiException(
        __('The maximum number of confirmation emails has already been reached for this subscriber.', 'mailpoet'),
        400,
        'mailpoet_subscribers_max_confirmations_reached'
      );
    }
    if ($reason === 'recently_sent') {
      throw new ApiException(
        __('A confirmation email was sent recently. Please wait before resending it.', 'mailpoet'),
        400,
        'mailpoet_subscribers_recently_sent'
      );
    }

    throw new ApiException(
      __('There was a problem with your sending method. Please check if your sending method is properly configured.', 'mailpoet'),
      500,
      'mailpoet_subscribers_sending_failed'
    );
  }

  public static function getRequestSchema(): array {
    return [
      'id' => Builder::integer()->required(),
    ];
  }
}

<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\RestApi\Endpoints;

use MailPoet\API\REST\ApiException;
use MailPoet\API\REST\Endpoint;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Config\AccessControl;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Validator\Builder;
use MailPoet\WP\Functions as WPFunctions;

/**
 * `POST /mailpoet/v1/newsletters/{id}/sending-status/resend`
 *
 * Resets a failed task subscriber back to unprocessed so the next cron run
 * retries the send. Replaces the legacy `sending_task_subscribers` `resend`
 * JSON action.
 */
class SendingStatusResendEndpoint extends Endpoint {
  /** @var ScheduledTaskSubscribersRepository */
  private $scheduledTaskSubscribersRepository;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    ScheduledTaskSubscribersRepository $scheduledTaskSubscribersRepository,
    SendingQueuesRepository $sendingQueuesRepository,
    WPFunctions $wp
  ) {
    $this->scheduledTaskSubscribersRepository = $scheduledTaskSubscribersRepository;
    $this->sendingQueuesRepository = $sendingQueuesRepository;
    $this->wp = $wp;
  }

  public function checkPermissions(): bool {
    return $this->wp->currentUserCan(AccessControl::PERMISSION_MANAGE_EMAILS);
  }

  public function handle(Request $request): Response {
    $newsletterIdParam = $request->getParam('id');
    $taskIdParam = $request->getParam('task_id');
    $subscriberIdParam = $request->getParam('subscriber_id');
    $newsletterId = is_numeric($newsletterIdParam) ? (int)$newsletterIdParam : 0;
    $taskId = is_numeric($taskIdParam) ? (int)$taskIdParam : 0;
    $subscriberId = is_numeric($subscriberIdParam) ? (int)$subscriberIdParam : 0;

    $taskSubscriber = $this->scheduledTaskSubscribersRepository->findOneBy([
      'task' => $taskId,
      'subscriber' => $subscriberId,
      'failed' => 1,
    ]);
    $sendingQueue = $this->sendingQueuesRepository->findOneBy(['task' => $taskId]);

    if (
      !$taskSubscriber
      || !$taskSubscriber->getTask()
      || !$sendingQueue
    ) {
      throw new ApiException(
        __('Failed sending task not found!', 'mailpoet'),
        404,
        'mailpoet_sending_status_task_not_found'
      );
    }

    $newsletter = $sendingQueue->getNewsletter();
    if (!$newsletter) {
      throw new ApiException(
        __('Newsletter not found!', 'mailpoet'),
        404,
        'mailpoet_sending_status_newsletter_not_found'
      );
    }

    // The task is addressed through `/newsletters/{id}/sending-status/resend`,
    // so it must belong to the newsletter named in the route. Without this the
    // body's `task_id` alone would decide which email is acted on, regardless
    // of the URL.
    if ((int)$newsletter->getId() !== $newsletterId) {
      throw new ApiException(
        __('Failed sending task not found!', 'mailpoet'),
        404,
        'mailpoet_sending_status_task_not_found'
      );
    }

    if ($newsletter->canBeSetActive() && $newsletter->getStatus() !== NewsletterEntity::STATUS_ACTIVE) {
      throw new ApiException(
        // translators: This error occurs when resending a failed email message to a recipient and the associated email definition (e.g., a welcome email, an automation email) is inactive.
        __('Failed to resend! The email is not active. Please activate it first.', 'mailpoet'),
        400,
        'mailpoet_sending_status_email_not_active'
      );
    }

    $taskSubscriber->resetToUnprocessed();
    $taskSubscriber->getTask()->setStatus(null);
    if (!$newsletter->canBeSetActive()) {
      $newsletter->setStatus(NewsletterEntity::STATUS_SENDING);
    }
    $this->scheduledTaskSubscribersRepository->flush();

    return new Response([]);
  }

  public static function getRequestSchema(): array {
    return [
      'id' => Builder::integer()->required(),
      'task_id' => Builder::integer()->required(),
      'subscriber_id' => Builder::integer()->required(),
    ];
  }
}

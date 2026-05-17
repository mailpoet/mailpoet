<?php declare(strict_types = 1);

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\ErrorResponse;
use MailPoet\API\JSON\Response;
use MailPoet\API\JSON\ResponseBuilders\SubscribersResponseBuilder;
use MailPoet\API\JSON\SuccessResponse;
use MailPoet\Config\AccessControl;
use MailPoet\ConflictException;
use MailPoet\Doctrine\Validator\ValidationException;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Exception;
use MailPoet\Listing;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\BulkActionController;
use MailPoet\Subscribers\BulkActionException;
use MailPoet\Subscribers\BulkConfirmationEmailResender;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Subscribers\SubscriberListingRepository;
use MailPoet\Subscribers\SubscriberSaveController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscribers\SubscriberSubscribeController;
use MailPoet\Util\Helpers;

class Subscribers extends APIEndpoint {
  const SUBSCRIPTION_LIMIT_COOLDOWN = 60;

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_SUBSCRIBERS,
    'methods' => ['subscribe' => AccessControl::NO_ACCESS_RESTRICTION],
  ];

  /** @var Listing\Handler */
  private $listingHandler;

  /** @var ConfirmationEmailMailer */
  private $confirmationEmailMailer;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SubscribersResponseBuilder */
  private $subscribersResponseBuilder;

  /** @var SubscriberListingRepository */
  private $subscriberListingRepository;

  /** @var SubscriberSaveController */
  private $saveController;

  /** @var SubscriberSubscribeController */
  private $subscribeController;

  /** @var SettingsController */
  private $settings;

  /** @var BulkActionController */
  private $bulkActionController;

  /** @var BulkConfirmationEmailResender */
  private $bulkConfirmationEmailResender;

  public function __construct(
    Listing\Handler $listingHandler,
    ConfirmationEmailMailer $confirmationEmailMailer,
    SubscribersRepository $subscribersRepository,
    SubscribersResponseBuilder $subscribersResponseBuilder,
    SubscriberListingRepository $subscriberListingRepository,
    SubscriberSaveController $saveController,
    SubscriberSubscribeController $subscribeController,
    SettingsController $settings,
    BulkActionController $bulkActionController,
    BulkConfirmationEmailResender $bulkConfirmationEmailResender
  ) {
    $this->listingHandler = $listingHandler;
    $this->confirmationEmailMailer = $confirmationEmailMailer;
    $this->subscribersRepository = $subscribersRepository;
    $this->subscribersResponseBuilder = $subscribersResponseBuilder;
    $this->subscriberListingRepository = $subscriberListingRepository;
    $this->saveController = $saveController;
    $this->subscribeController = $subscribeController;
    $this->settings = $settings;
    $this->bulkActionController = $bulkActionController;
    $this->bulkConfirmationEmailResender = $bulkConfirmationEmailResender;
  }

  public function get($data = []) {
    $subscriber = $this->getSubscriber($data);
    if (!$subscriber instanceof SubscriberEntity) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This subscriber does not exist.', 'mailpoet'),
      ]);
    }
    $result = $this->subscribersResponseBuilder->build($subscriber);
    return $this->successResponse($result);
  }

  /**
   * @deprecated Use the REST endpoint `GET /mailpoet/v1/subscribers` instead.
   *   Kept callable for third-party integrations posting to the legacy JSON API.
   */
  public function listing($data = []) {
    $definition = $this->listingHandler->getListingDefinition($data);
    $items = $this->subscriberListingRepository->getData($definition);
    $count = $this->subscriberListingRepository->getCount($definition);
    $filters = $this->subscriberListingRepository->getFilters($definition);
    $groups = $this->subscriberListingRepository->getGroups($definition);
    $subscribers = $this->subscribersResponseBuilder->buildForListing($items);
    if ($data['filter']['segment'] ?? false) {
      foreach ($subscribers as $key => $subscriber) {
        $subscribers[$key] = $this->preferUnsubscribedStatusFromSegment($subscriber, $data['filter']['segment']);
      }
    }
    return $this->successResponse($subscribers, [
      'count' => $count,
      'filters' => $filters,
      'groups' => $groups,
    ]);
  }

  private function preferUnsubscribedStatusFromSegment(array $subscriber, $segmentId) {
    $segmentStatus = $this->findSegmentStatus($subscriber, $segmentId);

    if ($segmentStatus === SubscriberEntity::STATUS_UNSUBSCRIBED) {
      $subscriber['status'] = $segmentStatus;
    }
    return $subscriber;
  }

  private function findSegmentStatus(array $subscriber, $segmentId) {
    foreach ($subscriber['subscriptions'] as $segment) {
      if ($segment['segment_id'] === $segmentId) {
        return $segment['status'];
      }
    }
  }

  public function subscribe($data = []) {
    try {
      $meta = $this->subscribeController->subscribe($data);
    } catch (Exception $exception) {
      return $this->badRequest([$exception->getMessage()]);
    }

    if (!empty($meta['error'])) {
      $errorMessage = $meta['error'];
      unset($meta['error']);
      return $this->badRequest([APIError::BAD_REQUEST => $errorMessage], $meta);
    }

    return $this->successResponse(
      [],
      $meta
    );
  }

  /**
   * @param array $data
   * @return ErrorResponse|SuccessResponse
   * @throws \Exception
   */
  public function save(array $data = []) {
    try {
      $subscriber = $this->saveController->save($data);
    } catch (ValidationException $validationException) {
      return $this->badRequest([$this->getErrorMessage($validationException)]);
    } catch (ConflictException $conflictException) {
      return $this->errorResponse([
        APIError::CONFLICT => $conflictException->getMessage(),
      ], [], Response::STATUS_CONFLICT);
    };

    return $this->successResponse(
      $this->subscribersResponseBuilder->build($subscriber)
    );
  }

  public function restore($data = []) {
    $subscriber = $this->getSubscriber($data);
    if ($subscriber instanceof SubscriberEntity) {
      $this->subscribersRepository->bulkRestore([$subscriber->getId()]);
      $this->subscribersRepository->refresh($subscriber);
      return $this->successResponse(
        $this->subscribersResponseBuilder->build($subscriber),
        ['count' => 1]
      );
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This subscriber does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function trash($data = []) {
    $subscriber = $this->getSubscriber($data);
    if ($subscriber instanceof SubscriberEntity) {
      $this->subscribersRepository->bulkTrash([$subscriber->getId()]);
      $this->subscribersRepository->refresh($subscriber);
      return $this->successResponse(
        $this->subscribersResponseBuilder->build($subscriber),
        ['count' => 1]
      );
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This subscriber does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function delete($data = []) {
    $subscriber = $this->getSubscriber($data);
    if ($subscriber instanceof SubscriberEntity) {
      $count = $this->subscribersRepository->bulkDelete([$subscriber->getId()]);
      return $this->successResponse(null, ['count' => $count]);
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This subscriber does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function sendConfirmationEmail($data = []) {
    if (!(bool)$this->settings->get('signup_confirmation.enabled', true)) {
      $errorMessage = __('Sign-up confirmation is disabled in your [link]MailPoet settings[/link]. Please enable it to resend confirmation emails or update your subscriber’s status manually.', 'mailpoet');
      $errorMessage = Helpers::replaceLinkTags($errorMessage, 'admin.php?page=mailpoet-settings#/signup');
      return $this->errorResponse([APIError::BAD_REQUEST => $errorMessage], [], Response::STATUS_BAD_REQUEST);
    }

    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $subscriber = $this->subscribersRepository->findOneById($id);
    if ($subscriber instanceof SubscriberEntity) {
      try {
        // Per-list confirmation settings are not resolved for manual resends;
        // the global default is used to avoid ambiguity across multiple segments.
        $result = $this->confirmationEmailMailer->sendAdminConfirmationEmail($subscriber);
        if ($result['status'] === 'sent') {
          return $this->successResponse();
        } else {
          $reason = $result['reason'] ?? null;
          if ($reason === 'max_confirmations_reached') {
            return $this->errorResponse([
              APIError::BAD_REQUEST => __('The maximum number of confirmation emails has already been reached for this subscriber.', 'mailpoet'),
            ], [], Response::STATUS_BAD_REQUEST);
          }
          if ($reason === 'recently_sent') {
            return $this->errorResponse([
              APIError::BAD_REQUEST => __('A confirmation email was sent recently. Please wait before resending it.', 'mailpoet'),
            ], [], Response::STATUS_BAD_REQUEST);
          }
          return $this->errorResponse([
            APIError::UNKNOWN => __('There was a problem with your sending method. Please check if your sending method is properly configured.', 'mailpoet'),
          ]);
        }
      } catch (\Exception $e) {
        return $this->errorResponse([
          APIError::UNKNOWN => __('There was a problem with your sending method. Please check if your sending method is properly configured.', 'mailpoet'),
        ]);
      }
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This subscriber does not exist.', 'mailpoet'),
      ]);
    }
  }

  /**
   * @deprecated Use the REST endpoint `POST /mailpoet/v1/subscribers/bulk-action`
   *   instead. Kept callable for third-party integrations that still post to the
   *   legacy JSON API. The orchestration lives in {@see BulkActionController}.
   */
  public function bulkAction($data = []) {
    $action = (string)($data['action'] ?? '');
    $definition = $this->listingHandler->getListingDefinition($data['listing']);

    if ($action === 'resendConfirmationEmails') {
      if (!$this->bulkConfirmationEmailResender->canCurrentUserResend()) {
        return $this->errorResponse([
          APIError::FORBIDDEN => __('You do not have permission to resend confirmation emails.', 'mailpoet'),
        ], [], Response::STATUS_FORBIDDEN);
      }
      if ($definition->getGroup() !== SubscriberEntity::STATUS_UNCONFIRMED) {
        return $this->badRequest([
          'invalid_group' => __('Confirmation emails can be resent in bulk only from the Unconfirmed subscribers view.', 'mailpoet'),
        ]);
      }
      if (!$this->bulkConfirmationEmailResender->isSignupConfirmationEnabled()) {
        return $this->errorResponse([
          'confirmation_disabled' => $this->bulkConfirmationEmailResender->getConfirmationDisabledMessage(),
        ], [], Response::STATUS_BAD_REQUEST);
      }
      return $this->successResponse($this->bulkConfirmationEmailResender->queue($definition, $data));
    }

    try {
      $result = $this->bulkActionController->execute($action, $definition, $data);
    } catch (BulkActionException $exception) {
      return $this->errorResponse(
        [$exception->getErrorCode() => $exception->getMessage()],
        [],
        $exception->getStatusCode()
      );
    }

    $meta = ['count' => $result['count']];
    if (isset($result['segment']['name'])) {
      $meta['segment'] = $result['segment']['name'];
    }
    if (isset($result['tag']['name'])) {
      $meta['tag'] = $result['tag']['name'];
    }
    return $this->successResponse(null, $meta);
  }

  /**
   * @param array $data
   * @return SubscriberEntity|null
   */
  private function getSubscriber($data) {
    return isset($data['id'])
      ? $this->subscribersRepository->findOneById((int)$data['id'])
      : null;
  }

  private function getErrorMessage(ValidationException $exception): string {
    $exceptionMessage = $exception->getMessage();
    if (strpos($exceptionMessage, 'This value should not be blank.') !== false) {
      return __('Please enter your email address', 'mailpoet');
    } elseif (strpos($exceptionMessage, 'This value is not a valid email address.') !== false) {
      return __('Your email address is invalid!', 'mailpoet');
    }

    return __('Unexpected error.', 'mailpoet');
  }
}

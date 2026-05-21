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
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\ConfirmationEmailMailer;
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

  /** @var ConfirmationEmailMailer */
  private $confirmationEmailMailer;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SubscribersResponseBuilder */
  private $subscribersResponseBuilder;

  /** @var SubscriberSaveController */
  private $saveController;

  /** @var SubscriberSubscribeController */
  private $subscribeController;

  /** @var SettingsController */
  private $settings;

  public function __construct(
    ConfirmationEmailMailer $confirmationEmailMailer,
    SubscribersRepository $subscribersRepository,
    SubscribersResponseBuilder $subscribersResponseBuilder,
    SubscriberSaveController $saveController,
    SubscriberSubscribeController $subscribeController,
    SettingsController $settings
  ) {
    $this->confirmationEmailMailer = $confirmationEmailMailer;
    $this->subscribersRepository = $subscribersRepository;
    $this->subscribersResponseBuilder = $subscribersResponseBuilder;
    $this->saveController = $saveController;
    $this->subscribeController = $subscribeController;
    $this->settings = $settings;
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

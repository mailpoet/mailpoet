<?php declare(strict_types = 1);

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\ResponseBuilders\SubscribersResponseBuilder;
use MailPoet\Config\AccessControl;
use MailPoet\Doctrine\Validator\ValidationException;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Exception;
use MailPoet\Listing;
use MailPoet\Models\Subscriber;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Subscribers\SubscriberListingRepository;
use MailPoet\Subscribers\SubscriberSaveController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscribers\SubscriberSubscribeController;
use MailPoet\UnexpectedValueException;
use MailPoet\WP\Functions as WPFunctions;

class Subscribers extends APIEndpoint {
  const SUBSCRIPTION_LIMIT_COOLDOWN = 60;

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_SUBSCRIBERS,
    'methods' => ['subscribe' => AccessControl::NO_ACCESS_RESTRICTION],
  ];

  /** @var Listing\Handler */
  private $listingHandler;

  /** @var ConfirmationEmailMailer; */
  private $confirmationEmailMailer;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SubscribersResponseBuilder */
  private $subscribersResponseBuilder;

  /** @var SubscriberListingRepository */
  private $subscriberListingRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SubscriberSaveController */
  private $saveController;

  /** @var SubscriberSubscribeController */
  private $subscribeController;

  public function __construct(
    Listing\Handler $listingHandler,
    ConfirmationEmailMailer $confirmationEmailMailer,
    SubscribersRepository $subscribersRepository,
    SubscribersResponseBuilder $subscribersResponseBuilder,
    SubscriberListingRepository $subscriberListingRepository,
    SegmentsRepository $segmentsRepository,
    SubscriberSaveController $saveController,
    SubscriberSubscribeController $subscribeController
  ) {
    $this->listingHandler = $listingHandler;
    $this->confirmationEmailMailer = $confirmationEmailMailer;
    $this->subscribersRepository = $subscribersRepository;
    $this->subscribersResponseBuilder = $subscribersResponseBuilder;
    $this->subscriberListingRepository = $subscriberListingRepository;
    $this->segmentsRepository = $segmentsRepository;
    $this->saveController = $saveController;
    $this->subscribeController = $subscribeController;
  }

  public function get($data = []) {
    $subscriber = $this->getSubscriber($data);
    if (!$subscriber instanceof SubscriberEntity) {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This subscriber does not exist.', 'mailpoet'),
      ]);
    }
    $result = $this->subscribersResponseBuilder->build($subscriber);
    return $this->successResponse($result);
  }

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

    if ($segmentStatus === Subscriber::STATUS_UNSUBSCRIBED) {
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

  public function save($data = []) {
    try {
      $subscriber = $this->saveController->save($data);
    } catch (ValidationException $validationException) {
      return $this->badRequest([$this->getErrorMessage($validationException)]);
    }

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
        APIError::NOT_FOUND => WPFunctions::get()->__('This subscriber does not exist.', 'mailpoet'),
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
        APIError::NOT_FOUND => WPFunctions::get()->__('This subscriber does not exist.', 'mailpoet'),
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
        APIError::NOT_FOUND => WPFunctions::get()->__('This subscriber does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function sendConfirmationEmail($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $subscriber = Subscriber::findOne($id);
    if ($subscriber instanceof Subscriber) {
      if ($this->confirmationEmailMailer->sendConfirmationEmail($subscriber)) {
        return $this->successResponse();
      }
      return $this->errorResponse([
        APIError::UNKNOWN => __('There was a problem with your sending method. Please check if your sending method is properly configured.', 'mailpoet'),
      ]);
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This subscriber does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function bulkAction($data = []) {
    $definition = $this->listingHandler->getListingDefinition($data['listing']);
    $ids = $this->subscriberListingRepository->getActionableIds($definition);

    $count = 0;
    $segment = null;
    if (isset($data['segment_id'])) {
      $segment = $this->getSegment($data);
      if (!$segment) {
        return $this->errorResponse([
          APIError::NOT_FOUND => WPFunctions::get()->__('This segment does not exist.', 'mailpoet'),
        ]);
      }
    }

    if ($data['action'] === 'trash') {
      $count = $this->subscribersRepository->bulkTrash($ids);
    } elseif ($data['action'] === 'restore') {
      $count = $this->subscribersRepository->bulkRestore($ids);
    } elseif ($data['action'] === 'delete') {
      $count = $this->subscribersRepository->bulkDelete($ids);
    } elseif ($data['action'] === 'removeFromAllLists') {
      $count = $this->subscribersRepository->bulkRemoveFromAllSegments($ids);
    } elseif ($data['action'] === 'removeFromList' && $segment instanceof SegmentEntity) {
      $count = $this->subscribersRepository->bulkRemoveFromSegment($segment, $ids);
    } elseif ($data['action'] === 'addToList' && $segment instanceof SegmentEntity) {
      $count = $this->subscribersRepository->bulkAddToSegment($segment, $ids);
    } elseif ($data['action'] === 'moveToList' && $segment instanceof SegmentEntity) {
      $count = $this->subscribersRepository->bulkMoveToSegment($segment, $ids);
    } elseif ($data['action'] === 'unsubscribe') {
      $count = $this->subscribersRepository->bulkUnsubscribe($ids);
    } else {
      throw UnexpectedValueException::create()
        ->withErrors([APIError::BAD_REQUEST => "Invalid bulk action '{$data['action']}' provided."]);
    }
    $meta = [
      'count' => $count,
    ];

    if ($segment) {
      $meta['segment'] = $segment->getName();
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

  private function getSegment(array $data): ?SegmentEntity {
    return isset($data['segment_id'])
      ? $this->segmentsRepository->findOneById((int)$data['segment_id'])
      : null;
  }

  private function getErrorMessage(ValidationException $exception): string {
    $exceptionMessage = $exception->getMessage();
    if (strpos($exceptionMessage, 'This value should not be blank.') !== false) {
      return WPFunctions::get()->__('Please enter your email address', 'mailpoet');
    } elseif (strpos($exceptionMessage, 'This value is not a valid email address.') !== false) {
      return WPFunctions::get()->__('Your email address is invalid!', 'mailpoet');
    }

    return WPFunctions::get()->__('Unexpected error.', 'mailpoet');
  }
}

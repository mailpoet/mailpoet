<?php

namespace MailPoet\API\JSON\v1;

use InvalidArgumentException;
use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\ResponseBuilders\SegmentsResponseBuilder;
use MailPoet\Config\AccessControl;
use MailPoet\Doctrine\Validator\ValidationException;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Listing;
use MailPoet\Models\Segment;
use MailPoet\Newsletter\Segment\NewsletterSegmentRepository;
use MailPoet\Segments\SegmentListingRepository;
use MailPoet\Segments\SegmentSaveController;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Segments\WooCommerce;
use MailPoet\Segments\WP;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WP\Functions as WPFunctions;

class Segments extends APIEndpoint {
  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_SEGMENTS,
  ];

  /** @var Listing\BulkActionController */
  private $bulkAction;

  /** @var Listing\Handler */
  private $listingHandler;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SegmentsResponseBuilder */
  private $segmentsResponseBuilder;

  /** @var SegmentSaveController */
  private $segmentSavecontroller;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var NewsletterSegmentRepository */
  private $newsletterSegmentRepository;

  /** @var WooCommerce */
  private $wooCommerceSync;

  /** @var WP */
  private $wpSegment;

  /** @var SegmentListingRepository */
  private $segmentListingRepository;

  public function __construct(
    Listing\BulkActionController $bulkAction,
    Listing\Handler $listingHandler,
    SegmentsRepository $segmentsRepository,
    SegmentListingRepository $segmentListingRepository,
    SegmentsResponseBuilder $segmentsResponseBuilder,
    SegmentSaveController $segmentSavecontroller,
    SubscribersRepository $subscribersRepository,
    WooCommerce $wooCommerce,
    WP $wpSegment
  ) {
    $this->bulkAction = $bulkAction;
    $this->listingHandler = $listingHandler;
    $this->wooCommerceSync = $wooCommerce;
    $this->segmentsRepository = $segmentsRepository;
    $this->segmentsResponseBuilder = $segmentsResponseBuilder;
    $this->segmentSavecontroller = $segmentSavecontroller;
    $this->subscribersRepository = $subscribersRepository;
    $this->wpSegment = $wpSegment;
    $this->segmentListingRepository = $segmentListingRepository;
  }

  public function get($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $segment = $this->segmentsRepository->findOneById($id);
    if ($segment instanceof SegmentEntity) {
      return $this->successResponse($this->segmentsResponseBuilder->build($segment));
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This list does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function listing($data = []) {
    $definition = $this->listingHandler->getListingDefinition($data);
    $items = $this->segmentListingRepository->getData($definition);
    $count = $this->segmentListingRepository->getCount($definition);
    $filters = $this->segmentListingRepository->getFilters($definition);
    $groups = $this->segmentListingRepository->getGroups($definition);
    $segments = $this->segmentsResponseBuilder->buildForListing($items);

//    $data = [];
//    foreach ($listingData['items'] as $segment) {
//
//      $segmentData = $segment
//        ->withSubscribersCount()
//        ->asArray();
      //$data[] = $segmentData;
//    }

    return $this->successResponse($segments, [
      'count' => $count,
      'filters' => $filters,
      'groups' => $groups,
    ]);
  }

  public function save($data = []) {
    try {
      $segment = $this->segmentSavecontroller->save($data);
    } catch (ValidationException $exception) {
      return $this->badRequest([
        APIError::BAD_REQUEST  => __('Please specify a name.', 'mailpoet'),
      ]);
    } catch (InvalidArgumentException $exception) {
      return $this->badRequest([
        APIError::BAD_REQUEST  => __('Another record already exists. Please specify a different "name".', 'mailpoet'),
      ]);
    }
    $response = $this->segmentsResponseBuilder->build($segment);
    return $this->successResponse($response);
  }

  public function restore($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $segment = Segment::findOne($id);
    if ($segment instanceof Segment) {
      // When the segment is of type WP_USERS we want to restore all its subscribers
      if ($segment->type === SegmentEntity::TYPE_WP_USERS) {
        $subscribers = $this->subscribersRepository->findBySegment((int)$segment->id);
        $subscriberIds = array_map(function (SubscriberEntity $subscriberEntity): int {
          return (int)$subscriberEntity->getId();
        }, $subscribers);
        $this->subscribersRepository->bulkRestore($subscriberIds);
      }

      $segment->restore();
      $segment = Segment::findOne($segment->id);
      if(!$segment instanceof Segment) return $this->errorResponse();
      return $this->successResponse(
        $segment->asArray(),
        ['count' => 1]
      );
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This list does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function trash($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $segment = Segment::findOne($id);
    if ($segment instanceof Segment) {
      // When the segment is of type WP_USERS we want to trash all subscribers who aren't subscribed in another list
      if ($segment->type === SegmentEntity::TYPE_WP_USERS) {
        $subscribers = $this->subscribersRepository->findExclusiveSubscribersBySegment((int)$segment->id);
        $subscriberIds = array_map(function (SubscriberEntity $subscriberEntity): int {
          return (int)$subscriberEntity->getId();
        }, $subscribers);
        $this->subscribersRepository->bulkTrash($subscriberIds);
      }

      $segment->trash();
      $segment = Segment::findOne($segment->id);
      if(!$segment instanceof Segment) return $this->errorResponse();
      return $this->successResponse(
        $segment->asArray(),
        ['count' => 1]
      );
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This list does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function delete($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $segment = Segment::findOne($id);
    if ($segment instanceof Segment) {
      $segment->delete();
      return $this->successResponse(null, ['count' => 1]);
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This list does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function duplicate($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $segment = Segment::findOne($id);

    if ($segment instanceof Segment) {
      $data = [
        'name' => sprintf(__('Copy of %s', 'mailpoet'), $segment->name),
      ];
      $duplicate = $segment->duplicate($data);
      $errors = $duplicate->getErrors();

      if (!empty($errors)) {
        return $this->errorResponse($errors);
      } else {
        $duplicate = Segment::findOne($duplicate->id);
        if(!$duplicate instanceof Segment) return $this->errorResponse();
        return $this->successResponse(
          $duplicate->asArray(),
          ['count' => 1]
        );
      }
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This list does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function synchronize($data) {
    try {
      if ($data['type'] === Segment::TYPE_WC_USERS) {
        $this->wooCommerceSync->synchronizeCustomers();
      } else {
        $this->wpSegment->synchronizeUsers();
      }
    } catch (\Exception $e) {
      return $this->errorResponse([
        $e->getCode() => $e->getMessage(),
      ]);
    }

    return $this->successResponse(null);
  }

  public function bulkAction($data = []) {
    try {
      $meta = $this->bulkAction->apply('\MailPoet\Models\Segment', $data);
      return $this->successResponse(null, $meta);
    } catch (\Exception $e) {
      return $this->errorResponse([
        $e->getCode() => $e->getMessage(),
      ]);
    }
  }
}

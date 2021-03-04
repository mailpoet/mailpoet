<?php

namespace MailPoet\API\JSON\v1;

use InvalidArgumentException;
use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error;
use MailPoet\API\JSON\Response;
use MailPoet\API\JSON\ResponseBuilders\DynamicSegmentsResponseBuilder;
use MailPoet\Config\AccessControl;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Listing\BulkActionController;
use MailPoet\Listing\Handler;
use MailPoet\Segments\DynamicSegments\DynamicSegmentsListingRepository;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Segments\DynamicSegments\SegmentSaveController;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\WP\Functions as WPFunctions;

class DynamicSegments extends APIEndpoint {

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_SEGMENTS,
  ];

  /** @var BulkActionController */
  private $bulkAction;

  /** @var Handler */
  private $listingHandler;

  /** @var DynamicSegmentsListingRepository */
  private $dynamicSegmentsListingRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var DynamicSegmentsResponseBuilder */
  private $segmentsResponseBuilder;

  /** @var SegmentSaveController */
  private $saveController;

  public function __construct(
    BulkActionController $bulkAction,
    Handler $handler,
    DynamicSegmentsListingRepository $dynamicSegmentsListingRepository,
    DynamicSegmentsResponseBuilder $segmentsResponseBuilder,
    SegmentsRepository $segmentsRepository,
    SegmentSaveController $saveController
  ) {
    $this->bulkAction = $bulkAction;
    $this->listingHandler = $handler;
    $this->dynamicSegmentsListingRepository = $dynamicSegmentsListingRepository;
    $this->segmentsResponseBuilder = $segmentsResponseBuilder;
    $this->segmentsRepository = $segmentsRepository;
    $this->saveController = $saveController;
  }

  public function get($data = []) {
    if (isset($data['id'])) {
      $id = (int)$data['id'];
    } else {
      return $this->errorResponse([
        Error::BAD_REQUEST => WPFunctions::get()->__('Missing mandatory argument `id`.', 'mailpoet'),
      ]);
    }

    $segment = $this->segmentsRepository->findOneById($id);
    if (!$segment instanceof SegmentEntity) {
      return $this->errorResponse([
        Error::NOT_FOUND => WPFunctions::get()->__('This segment does not exist.', 'mailpoet'),
      ]);
    }

    return $this->successResponse($this->segmentsResponseBuilder->build($segment));
  }

  public function save($data) {
    try {
      $segment = $this->saveController->save($data);
      return $this->successResponse($this->segmentsResponseBuilder->build($segment));
    } catch (InvalidFilterException $e) {
      return $this->errorResponse([
        Error::BAD_REQUEST => $this->getErrorString($e),
      ], [], Response::STATUS_BAD_REQUEST);
    } catch (InvalidArgumentException $e) {
      return $this->badRequest([
        Error::BAD_REQUEST  => __('Another record already exists. Please specify a different "name".', 'mailpoet'),
      ]);
    }
  }

  private function getErrorString(InvalidFilterException $e) {
    switch ($e->getCode()) {
      case InvalidFilterException::MISSING_TYPE:
        return WPFunctions::get()->__('Segment type is missing.', 'mailpoet');
      case InvalidFilterException::INVALID_TYPE:
        return WPFunctions::get()->__('Segment type is unknown.', 'mailpoet');
      case InvalidFilterException::MISSING_ROLE:
        return WPFunctions::get()->__('Please select user role.', 'mailpoet');
      case InvalidFilterException::MISSING_ACTION:
        return WPFunctions::get()->__('Please select email action.', 'mailpoet');
      case InvalidFilterException::MISSING_NEWSLETTER_ID:
        return WPFunctions::get()->__('Please select an email.', 'mailpoet');
      case InvalidFilterException::MISSING_PRODUCT_ID:
        return WPFunctions::get()->__('Please select category.', 'mailpoet');
      case InvalidFilterException::MISSING_CATEGORY_ID:
        return WPFunctions::get()->__('Please select product.', 'mailpoet');
      default:
        return WPFunctions::get()->__('An error occurred while saving data.', 'mailpoet');
    }
  }

  public function trash($data = []) {
    if (!isset($data['id'])) {
      return $this->errorResponse([
        Error::BAD_REQUEST => WPFunctions::get()->__('Missing mandatory argument `id`.', 'mailpoet'),
      ]);
    }

    $segment = $this->getSegment($data);
    if ($segment === null) {
      return $this->errorResponse([
        Error::NOT_FOUND => WPFunctions::get()->__('This segment does not exist.', 'mailpoet'),
      ]);
    }

    $this->segmentsRepository->bulkTrash([$segment->getId()], SegmentEntity::TYPE_DYNAMIC);
    return $this->successResponse(
      $this->segmentsResponseBuilder->build($segment),
      ['count' => 1]
    );
  }

  public function restore($data = []) {
    if (!isset($data['id'])) {
      return $this->errorResponse([
        Error::BAD_REQUEST => WPFunctions::get()->__('Missing mandatory argument `id`.', 'mailpoet'),
      ]);
    }

    $segment = $this->getSegment($data);
    if ($segment === null) {
      return $this->errorResponse([
        Error::NOT_FOUND => WPFunctions::get()->__('This segment does not exist.', 'mailpoet'),
      ]);
    }

    $this->segmentsRepository->bulkRestore([$segment->getId()], SegmentEntity::TYPE_DYNAMIC);
    return $this->successResponse(
      $this->segmentsResponseBuilder->build($segment),
      ['count' => 1]
    );
  }

  public function delete($data = []) {
    if (!isset($data['id'])) {
      return $this->errorResponse([
        Error::BAD_REQUEST => WPFunctions::get()->__('Missing mandatory argument `id`.', 'mailpoet'),
      ]);
    }

    $segment = $this->getSegment($data);
    if ($segment === null) {
      return $this->errorResponse([
        Error::NOT_FOUND => WPFunctions::get()->__('This segment does not exist.', 'mailpoet'),
      ]);
    }

    $this->segmentsRepository->bulkDelete([$segment->getId()], SegmentEntity::TYPE_DYNAMIC);
    return $this->successResponse(null, ['count' => 1]);
  }

  public function listing($data = []) {
    $definition = $this->listingHandler->getListingDefinition($data);
    $items = $this->dynamicSegmentsListingRepository->getData($definition);
    $count = $this->dynamicSegmentsListingRepository->getCount($definition);
    $filters = $this->dynamicSegmentsListingRepository->getFilters($definition);
    $groups = $this->dynamicSegmentsListingRepository->getGroups($definition);
    $segments = $this->segmentsResponseBuilder->buildForListing($items);

    return $this->successResponse($segments, [
      'count' => $count,
      'filters' => $filters,
      'groups' => $groups,
    ]);
  }

  public function bulkAction($data = []) {
    try {
      $meta = $this->bulkAction->apply('\MailPoet\Models\DynamicSegment', $data);
      return $this->successResponse(null, $meta);
    } catch (\Exception $e) {
      return $this->errorResponse([
        $e->getCode() => $e->getMessage(),
      ]);
    }
  }

  private function getSegment(array $data): ?SegmentEntity {
    return isset($data['id'])
      ? $this->segmentsRepository->findOneById((int)$data['id'])
      : null;
  }
}

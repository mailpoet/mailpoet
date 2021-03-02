<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error;
use MailPoet\API\JSON\Response;
use MailPoet\API\JSON\ResponseBuilders\DynamicSegmentsResponseBuilder;
use MailPoet\Config\AccessControl;
use MailPoet\DynamicSegments\Exceptions\ErrorSavingException;
use MailPoet\DynamicSegments\Exceptions\InvalidSegmentTypeException;
use MailPoet\DynamicSegments\Mappers\DBMapper;
use MailPoet\DynamicSegments\Mappers\FormDataMapper;
use MailPoet\DynamicSegments\Persistence\Loading\SingleSegmentLoader;
use MailPoet\DynamicSegments\Persistence\Saver;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Listing\BulkActionController;
use MailPoet\Listing\Handler;
use MailPoet\Models\Model;
use MailPoet\Segments\DynamicSegments\DynamicSegmentsListingRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\WP\Functions as WPFunctions;

class DynamicSegments extends APIEndpoint {

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_SEGMENTS,
  ];

  /** @var FormDataMapper */
  private $mapper;

  /** @var Saver */
  private $saver;

  /** @var SingleSegmentLoader */
  private $dynamicSegmentsLoader;

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

  public function __construct(
    BulkActionController $bulkAction,
    Handler $handler,
    DynamicSegmentsListingRepository $dynamicSegmentsListingRepository,
    DynamicSegmentsResponseBuilder $segmentsResponseBuilder,
    SegmentsRepository $segmentsRepository,
    $mapper = null,
    $saver = null,
    $dynamicSegmentsLoader = null
  ) {
    $this->bulkAction = $bulkAction;
    $this->listingHandler = $handler;
    $this->mapper = $mapper ?: new FormDataMapper();
    $this->saver = $saver ?: new Saver();
    $this->dynamicSegmentsLoader = $dynamicSegmentsLoader ?: new SingleSegmentLoader(new DBMapper());
    $this->dynamicSegmentsListingRepository = $dynamicSegmentsListingRepository;
    $this->segmentsResponseBuilder = $segmentsResponseBuilder;
    $this->segmentsRepository = $segmentsRepository;
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
      $dynamicSegment = $this->mapper->mapDataToDB($data);
      $this->saver->save($dynamicSegment);

      return $this->successResponse($data);
    } catch (InvalidSegmentTypeException $e) {
      return $this->errorResponse([
        Error::BAD_REQUEST => $this->getErrorString($e),
      ], [], Response::STATUS_BAD_REQUEST);
    } catch (ErrorSavingException $e) {
      $statusCode = Response::STATUS_UNKNOWN;
      if ($e->getCode() === Model::DUPLICATE_RECORD) {
        $statusCode = Response::STATUS_CONFLICT;
      }
      return $this->errorResponse([$statusCode => $e->getMessage()], [], $statusCode);
    }
  }

  private function getErrorString(InvalidSegmentTypeException $e) {
    switch ($e->getCode()) {
      case InvalidSegmentTypeException::MISSING_TYPE:
        return WPFunctions::get()->__('Segment type is missing.', 'mailpoet');
      case InvalidSegmentTypeException::INVALID_TYPE:
        return WPFunctions::get()->__('Segment type is unknown.', 'mailpoet');
      case InvalidSegmentTypeException::MISSING_ROLE:
        return WPFunctions::get()->__('Please select user role.', 'mailpoet');
      case InvalidSegmentTypeException::MISSING_ACTION:
        return WPFunctions::get()->__('Please select email action.', 'mailpoet');
      case InvalidSegmentTypeException::MISSING_NEWSLETTER_ID:
        return WPFunctions::get()->__('Please select an email.', 'mailpoet');
      case InvalidSegmentTypeException::MISSING_PRODUCT_ID:
        return WPFunctions::get()->__('Please select category.', 'mailpoet');
      case InvalidSegmentTypeException::MISSING_CATEGORY_ID:
        return WPFunctions::get()->__('Please select product.', 'mailpoet');
      default:
        return WPFunctions::get()->__('An error occurred while saving data.', 'mailpoet');
    }
  }

  public function trash($data = []) {
    if (isset($data['id'])) {
      $id = (int)$data['id'];
    } else {
      return $this->errorResponse([
        Error::BAD_REQUEST => WPFunctions::get()->__('Missing mandatory argument `id`.', 'mailpoet'),
      ]);
    }

    try {
      $segment = $this->dynamicSegmentsLoader->load($id);
      $segment->trash();
      return $this->successResponse(
        $segment->asArray(),
        ['count' => 1]
      );
    } catch (\InvalidArgumentException $e) {
      return $this->errorResponse([
        Error::NOT_FOUND => WPFunctions::get()->__('This segment does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function restore($data = []) {
    if (isset($data['id'])) {
      $id = (int)$data['id'];
    } else {
      return $this->errorResponse([
        Error::BAD_REQUEST => WPFunctions::get()->__('Missing mandatory argument `id`.', 'mailpoet'),
      ]);
    }

    try {
      $segment = $this->dynamicSegmentsLoader->load($id);
      $segment->restore();
      return $this->successResponse(
        $segment->asArray(),
        ['count' => 1]
      );
    } catch (\InvalidArgumentException $e) {
      return $this->errorResponse([
        Error::NOT_FOUND => WPFunctions::get()->__('This segment does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function delete($data = []) {
    if (isset($data['id'])) {
      $id = (int)$data['id'];
    } else {
      return $this->errorResponse([
        Error::BAD_REQUEST => WPFunctions::get()->__('Missing mandatory argument `id`.', 'mailpoet'),
      ]);
    }

    try {
      $segment = $this->dynamicSegmentsLoader->load($id);
      $segment->delete();
      return $this->successResponse(null, ['count' => 1]);
    } catch (\InvalidArgumentException $e) {
      return $this->errorResponse([
        Error::NOT_FOUND => WPFunctions::get()->__('This segment does not exist.', 'mailpoet'),
      ]);
    }
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
}

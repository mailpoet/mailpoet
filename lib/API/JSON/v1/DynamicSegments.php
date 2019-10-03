<?php

namespace MailPoet\Premium\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error;
use MailPoet\API\JSON\Response;
use MailPoet\Config\AccessControl;
use MailPoet\Listing\BulkActionController;
use MailPoet\Listing\Handler;
use MailPoet\Models\Model;
use MailPoet\Premium\DynamicSegments\Exceptions\ErrorSavingException;
use MailPoet\Premium\DynamicSegments\Exceptions\InvalidSegmentTypeException;
use MailPoet\Premium\DynamicSegments\Mappers\DBMapper;
use MailPoet\Premium\DynamicSegments\Mappers\FormDataMapper;
use MailPoet\Premium\DynamicSegments\Persistence\Loading\SingleSegmentLoader;
use MailPoet\Premium\DynamicSegments\Persistence\Loading\SubscribersCount;
use MailPoet\Premium\DynamicSegments\Persistence\Saver;
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
  private $dynamic_segments_loader;

  /** @var SubscribersCount */
  private $subscribers_counts_loader;

  /** @var BulkActionController */
  private $bulk_action;

  /** @var Handler */
  private $listing_handler;

  public function __construct(BulkActionController $bulk_action, Handler $handler, $mapper = null, $saver = null, $dynamic_segments_loader = null, $subscribers_counts_loader = null) {
    $this->bulk_action = $bulk_action;
    $this->listing_handler = $handler;
    $this->mapper = $mapper ?: new FormDataMapper();
    $this->saver = $saver ?: new Saver();
    $this->dynamic_segments_loader = $dynamic_segments_loader ?: new SingleSegmentLoader(new DBMapper());
    $this->subscribers_counts_loader = $subscribers_counts_loader ?: new SubscribersCount();
  }

  function get($data = []) {
    if (isset($data['id'])) {
      $id = (int)$data['id'];
    } else {
      return $this->errorResponse([
        Error::BAD_REQUEST => WPFunctions::get()->__('Missing mandatory argument `id`.', 'mailpoet-premium'),
      ]);
    }

    try {
      $segment = $this->dynamic_segments_loader->load($id);

      $filters = $segment->getFilters();

      return $this->successResponse(array_merge([
        'name' => $segment->name,
        'description' => $segment->description,
        'id' => $segment->id,
      ], $filters[0]->toArray()));
    } catch (\InvalidArgumentException $e) {
      return $this->errorResponse([
        Error::NOT_FOUND => WPFunctions::get()->__('This segment does not exist.', 'mailpoet-premium'),
      ]);
    }
  }

  function save($data) {
    try {
      $dynamic_segment = $this->mapper->mapDataToDB($data);
      $this->saver->save($dynamic_segment);

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
        return WPFunctions::get()->__('Segment type is missing.', 'mailpoet-premium');
      case InvalidSegmentTypeException::INVALID_TYPE:
        return WPFunctions::get()->__('Segment type is unknown.', 'mailpoet-premium');
      case InvalidSegmentTypeException::MISSING_ROLE:
        return WPFunctions::get()->__('Please select user role.', 'mailpoet-premium');
      case InvalidSegmentTypeException::MISSING_ACTION:
        return WPFunctions::get()->__('Please select email action.', 'mailpoet-premium');
      case InvalidSegmentTypeException::MISSING_NEWSLETTER_ID:
        return WPFunctions::get()->__('Please select an email.', 'mailpoet-premium');
      case InvalidSegmentTypeException::MISSING_PRODUCT_ID:
        return WPFunctions::get()->__('Please select category.', 'mailpoet-premium');
      case InvalidSegmentTypeException::MISSING_CATEGORY_ID:
        return WPFunctions::get()->__('Please select product.', 'mailpoet-premium');
      default:
        return WPFunctions::get()->__('An error occurred while saving data.', 'mailpoet-premium');
    }
  }

  function trash($data = []) {
    if (isset($data['id'])) {
      $id = (int)$data['id'];
    } else {
      return $this->errorResponse([
        Error::BAD_REQUEST => WPFunctions::get()->__('Missing mandatory argument `id`.', 'mailpoet-premium'),
      ]);
    }

    try {
      $segment = $this->dynamic_segments_loader->load($id);
      $segment->trash();
      return $this->successResponse(
        $segment->asArray(),
        ['count' => 1]
      );
    } catch (\InvalidArgumentException $e) {
      return $this->errorResponse([
        Error::NOT_FOUND => WPFunctions::get()->__('This segment does not exist.', 'mailpoet-premium'),
      ]);
    }
  }

  function restore($data = []) {
    if (isset($data['id'])) {
      $id = (int)$data['id'];
    } else {
      return $this->errorResponse([
        Error::BAD_REQUEST => WPFunctions::get()->__('Missing mandatory argument `id`.', 'mailpoet-premium'),
      ]);
    }

    try {
      $segment = $this->dynamic_segments_loader->load($id);
      $segment->restore();
      return $this->successResponse(
        $segment->asArray(),
        ['count' => 1]
      );
    } catch (\InvalidArgumentException $e) {
      return $this->errorResponse([
        Error::NOT_FOUND => WPFunctions::get()->__('This segment does not exist.', 'mailpoet-premium'),
      ]);
    }
  }

  function delete($data = []) {
    if (isset($data['id'])) {
      $id = (int)$data['id'];
    } else {
      return $this->errorResponse([
        Error::BAD_REQUEST => WPFunctions::get()->__('Missing mandatory argument `id`.', 'mailpoet-premium'),
      ]);
    }

    try {
      $segment = $this->dynamic_segments_loader->load($id);
      $segment->delete();
      return $this->successResponse(null, ['count' => 1]);
    } catch (\InvalidArgumentException $e) {
      return $this->errorResponse([
        Error::NOT_FOUND => WPFunctions::get()->__('This segment does not exist.', 'mailpoet-premium'),
      ]);
    }
  }

  function listing($data = []) {
    $listing_data = $this->listing_handler->get('\MailPoet\Premium\Models\DynamicSegment', $data);

    $data = [];
    foreach ($listing_data['items'] as $segment) {
      $segment->subscribers_url = WPFunctions::get()->adminUrl(
        'admin.php?page=mailpoet-subscribers#/filter[segment=' . $segment->id . ']'
      );

      $row = $segment->asArray();
      $segment_with_filters = $this->dynamic_segments_loader->load($segment->id);
      $row['count'] = $this->subscribers_counts_loader->getSubscribersCount($segment_with_filters);
      $data[] = $row;
    }

    return $this->successResponse($data, [
      'count' => $listing_data['count'],
      'filters' => $listing_data['filters'],
      'groups' => $listing_data['groups'],
    ]);

  }

  function bulkAction($data = []) {
    try {
      $meta = $this->bulk_action->apply('\MailPoet\Premium\Models\DynamicSegment', $data);
      return $this->successResponse(null, $meta);
    } catch (\Exception $e) {
      return $this->errorResponse([
        $e->getCode() => $e->getMessage(),
      ]);
    }
  }
}

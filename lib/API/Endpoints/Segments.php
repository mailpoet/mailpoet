<?php
namespace MailPoet\API\Endpoints;
use \MailPoet\API\Endpoint as APIEndpoint;
use \MailPoet\API\Error as APIError;

use \MailPoet\Models\Segment;
use \MailPoet\Models\SubscriberSegment;
use \MailPoet\Models\SegmentFilter;
use \MailPoet\Listing;
use \MailPoet\Segments\WP;

if(!defined('ABSPATH')) exit;

class Segments extends APIEndpoint {
  function get($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $segment = Segment::findOne($id);
    if($segment === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This list does not exist.')
      ));
    } else {
      return $this->successResponse($segment->asArray());
    }
  }

  function listing($data = array()) {
    $listing = new Listing\Handler(
      '\MailPoet\Models\Segment',
      $data
    );

    $listing_data = $listing->get();

    // fetch segments relations for each returned item
    foreach($listing_data['items'] as $key => $segment) {
      $segment->subscribers_url = admin_url(
        'admin.php?page=mailpoet-subscribers#/filter[segment='.$segment->id.']'
      );

      $listing_data['items'][$key] = $segment
        ->withSubscribersCount()
        ->asArray();
    }

    return $listing_data;
  }

  function save($data = array()) {
    $segment = Segment::createOrUpdate($data);
    $errors = $segment->getErrors();

    if(!empty($errors)) {
      return $this->errorResponse($errors);
    } else {
      return $this->successResponse(
        Segment::findOne($segment->id)->asArray()
      );
    }
  }

  function restore($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $segment = Segment::findOne($id);
    if($segment === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This list does not exist.')
      ));
    } else {
      $segment->restore();
      return $this->successResponse(
        Segment::findOne($segment->id)->asArray(),
        array('count' => 1)
      );
    }
  }

  function trash($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $segment = Segment::findOne($id);
    if($segment === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This list does not exist.')
      ));
    } else {
      $segment->trash();
      return $this->successResponse(
        Segment::findOne($segment->id)->asArray(),
        array('count' => 1)
      );
    }
  }

  function delete($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $segment = Segment::findOne($id);
    if($segment === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This list does not exist.')
      ));
    } else {
      $segment->delete();
      return $this->successResponse(null, array('count' => 1));
    }
  }

  function duplicate($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $segment = Segment::findOne($id);

    if($segment === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This list does not exist.')
      ));
    } else {
      $data = array(
        'name' => sprintf(__('Copy of %s'), $segment->name)
      );
      $duplicate = $segment->duplicate($data);
      $errors = $duplicate->getErrors();

      if(!empty($errors)) {
        return $this->errorResponse($errors);
      } else {
        return $this->successResponse(
          Segment::findOne($duplicate->id)->asArray(),
          array('count' => 1)
        );
      }
    }
  }

  function synchronize() {
    try {
      WP::synchronizeUsers();
    } catch(\Exception $e) {
      return $this->errorResponse(array(
        $e->getCode() => $e->getMessage()
      ));
    }

    return $this->successResponse(null);
  }

  function bulkAction($data = array()) {
    try {
      $bulk_action = new Listing\BulkAction(
        '\MailPoet\Models\Segment',
        $data
      );
      $count = $bulk_action->apply();
      return $this->successResponse(null, array('count' => $count));
    } catch(\Exception $e) {
      return $this->errorResponse(array(
        $e->getCode() => $e->getMessage()
      ));
    }
  }
}

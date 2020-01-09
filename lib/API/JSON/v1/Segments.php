<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Config\AccessControl;
use MailPoet\Listing;
use MailPoet\Models\Segment;
use MailPoet\Segments\WooCommerce;
use MailPoet\Segments\WP;
use MailPoet\WP\Functions as WPFunctions;

class Segments extends APIEndpoint {
  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_SEGMENTS,
  ];

  /** @var Listing\BulkActionController */
  private $bulk_action;

  /** @var Listing\Handler */
  private $listing_handler;

  /** @var WooCommerce */
  private $woo_commerce_sync;

  public function __construct(
    Listing\BulkActionController $bulkAction,
    Listing\Handler $listingHandler,
    WooCommerce $wooCommerce
  ) {
    $this->bulkAction = $bulkAction;
    $this->listingHandler = $listingHandler;
    $this->wooCommerceSync = $wooCommerce;
  }

  public function get($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $segment = Segment::findOne($id);
    if ($segment instanceof Segment) {
      return $this->successResponse($segment->asArray());
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This list does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function listing($data = []) {
    $listingData = $this->listingHandler->get('\MailPoet\Models\Segment', $data);

    $data = [];
    foreach ($listingData['items'] as $segment) {
      $segment->subscribersUrl = WPFunctions::get()->adminUrl(
        'admin.php?page=mailpoet-subscribers#/filter[segment=' . $segment->id . ']'
      );

      $data[] = $segment
        ->withSubscribersCount()
        ->withAutomatedEmailsSubjects()
        ->asArray();
    }

    return $this->successResponse($data, [
      'count' => $listingData['count'],
      'filters' => $listingData['filters'],
      'groups' => $listingData['groups'],
    ]);
  }

  public function save($data = []) {
    $segment = Segment::createOrUpdate($data);
    $errors = $segment->getErrors();

    if (!empty($errors)) {
      return $this->badRequest($errors);
    } else {
      $segment = Segment::findOne($segment->id);
      if(!$segment instanceof Segment) return $this->errorResponse();
      return $this->successResponse(
        $segment->asArray()
      );
    }
  }

  public function restore($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $segment = Segment::findOne($id);
    if ($segment instanceof Segment) {
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
        WP::synchronizeUsers();
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

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

if (!defined('ABSPATH')) exit;

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

  function __construct(
    Listing\BulkActionController $bulk_action,
    Listing\Handler $listing_handler,
    WooCommerce $woo_commerce
  ) {
    $this->bulk_action = $bulk_action;
    $this->listing_handler = $listing_handler;
    $this->woo_commerce_sync = $woo_commerce;
  }

  function get($data = []) {
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

  function listing($data = []) {
    $listing_data = $this->listing_handler->get('\MailPoet\Models\Segment', $data);

    $data = [];
    foreach ($listing_data['items'] as $segment) {
      $segment->subscribers_url = WPFunctions::get()->adminUrl(
        'admin.php?page=mailpoet-subscribers#/filter[segment=' . $segment->id . ']'
      );

      $data[] = $segment
        ->withSubscribersCount()
        ->asArray();
    }

    return $this->successResponse($data, [
      'count' => $listing_data['count'],
      'filters' => $listing_data['filters'],
      'groups' => $listing_data['groups'],
    ]);
  }

  function save($data = []) {
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

  function restore($data = []) {
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

  function trash($data = []) {
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

  function delete($data = []) {
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

  function duplicate($data = []) {
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

  function synchronize($data) {
    try {
      if ($data['type'] === Segment::TYPE_WC_USERS) {
        $this->woo_commerce_sync->synchronizeCustomers();
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

  function bulkAction($data = []) {
    try {
      $meta = $this->bulk_action->apply('\MailPoet\Models\Segment', $data);
      return $this->successResponse(null, $meta);
    } catch (\Exception $e) {
      return $this->errorResponse([
        $e->getCode() => $e->getMessage(),
      ]);
    }
  }
}

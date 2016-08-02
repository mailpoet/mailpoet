<?php
namespace MailPoet\API\Endpoints;

use \MailPoet\Models\Segment;
use \MailPoet\Models\SubscriberSegment;
use \MailPoet\Models\SegmentFilter;
use \MailPoet\Listing;
use \MailPoet\Segments\WP;

if(!defined('ABSPATH')) exit;

class Segments {
  function __construct() {
  }

  function get($id = false) {
    $segment = Segment::findOne($id);
    if($segment === false) {
      return false;
    } else {
      return $segment->asArray();
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
      return array(
        'result' => false,
        'errors' => $errors
      );
    } else {
      return array(
        'result' => true
      );
    }
  }

  function restore($id) {
    $segment = Segment::findOne($id);
    if($segment !== false) {
      $segment->restore();
    }
    return ($segment->getErrors() === false);
  }

  function trash($id) {
    $segment = Segment::findOne($id);
    if($segment !== false) {
      $segment->trash();
    }
    return ($segment->getErrors() === false);
  }

  function delete($id) {
    $segment = Segment::findOne($id);
    if($segment !== false) {
      $segment->delete();
      return 1;
    }
    return false;
  }

  function duplicate($id) {
    $result = false;

    $segment = Segment::findOne($id);
    if($segment !== false) {
      $data = array(
        'name' => sprintf(__('Copy of %s'), $segment->name)
      );
      $result = $segment->duplicate($data)->asArray();
    }

    return $result;
  }

  function synchronize() {
    $result = WP::synchronizeUsers();

    return $result;
  }

  function bulkAction($data = array()) {
    $bulk_action = new Listing\BulkAction(
      '\MailPoet\Models\Segment',
      $data
    );

    return $bulk_action->apply();
  }
}

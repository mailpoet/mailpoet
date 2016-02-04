<?php
namespace MailPoet\Router;
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
    foreach($listing_data['items'] as &$item) {
      $stats = SubscriberSegment::table_alias('relation')
        ->where(
          'relation.segment_id',
          $item['id']
        )
        ->join(
          MP_SUBSCRIBERS_TABLE,
          'subscribers.id = relation.subscriber_id',
          'subscribers'
        )
        ->select_expr(
          'SUM(CASE subscribers.status WHEN "subscribed" THEN 1 ELSE 0 END)',
          'subscribed'
        )
        ->select_expr(
          'SUM(CASE subscribers.status WHEN "unsubscribed" THEN 1 ELSE 0 END)',
          'unsubscribed'
        )
        ->select_expr(
          'SUM(CASE subscribers.status WHEN "unconfirmed" THEN 1 ELSE 0 END)',
          'unconfirmed'
        )
        ->findOne()->asArray();

      $item = array_merge($item, $stats);

      $item['subscribers_url'] = admin_url(
        'admin.php?page=mailpoet-subscribers#/filter[segment='.$item['id'].']'
      );
    }

    return $listing_data;
  }

  function getAll() {
    return Segment::findArray();
  }

  function save($data = array()) {
    $errors = array();
    $result = false;

    $segment = Segment::createOrUpdate($data);

    if($segment !== false && !$segment->id()) {
      $errors = $segment->getValidationErrors();
    } else {
      $result = true;
    }
    return array(
      'result' => $result,
      'errors' => $errors
    );
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

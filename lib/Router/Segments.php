<?php
namespace MailPoet\Router;
use \MailPoet\Models\Segment;
use \MailPoet\Models\SubscriberSegment;
use \MailPoet\Listing;

if(!defined('ABSPATH')) exit;

class Segments {
  function __construct() {
  }

  function get($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : 0);

    $segment = Segment::findOne($id);
    if($segment === false) {
      wp_send_json(false);
    } else {
      wp_send_json($segment->asArray());
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
          'SUM(CASE status WHEN "subscribed" THEN 1 ELSE 0 END)',
          'subscribed'
        )
        ->select_expr(
          'SUM(CASE status WHEN "unsubscribed" THEN 1 ELSE 0 END)',
          'unsubscribed'
        )
        ->select_expr(
          'SUM(CASE status WHEN "unconfirmed" THEN 1 ELSE 0 END)',
          'unconfirmed'
        )
        ->findOne()->asArray();

      $item = array_merge($item, $stats);

      $item['subscribers_url'] = admin_url(
        'admin.php?page=mailpoet-subscribers#/filter[segment='.$item['id'].']'
      );
    }

    wp_send_json($listing_data);
  }

  function getAll() {
    $collection = Segment::findArray();
    wp_send_json($collection);
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
    wp_send_json(array(
      'result' => $result,
      'errors' => $errors
    ));
  }

  function restore($id) {
    $result = false;

    $segment = Segment::findOne($id);
    if($segment !== false) {
      $result = $segment->restore();
    }

    wp_send_json($result);
  }

  function trash($id) {
    $result = false;

    $segment = Segment::findOne($id);
    if($segment !== false) {
      $result = $segment->trash();
    }

    wp_send_json($result);
  }

  function delete($id) {
    $result = false;

    $segment = Segment::findOne($id);
    if($segment !== false) {
      $segment->delete();
      $result = 1;
    }

    wp_send_json($result);
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

    wp_send_json($result);
  }

  function bulkAction($data = array()) {
    $bulk_action = new Listing\BulkAction(
      '\MailPoet\Models\Segment',
      $data
    );

    wp_send_json($bulk_action->apply());
  }
}

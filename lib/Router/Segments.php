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
        'admin.php?page=mailpoet-subscribers#segment='.$item['id']
      );
    }

    wp_send_json($listing_data);
  }

  function getAll() {
    $collection = Segment::find_array();
    wp_send_json($collection);
  }

  function save($data = array()) {
    $result = Segment::createOrUpdate($data);

    if($result !== true) {
      wp_send_json($result);
    } else {
      wp_send_json(true);
    }
  }

  function restore($id) {
    $segment = Segment::findOne($id);
    if($segment !== false) {
      $segment->set_expr('deleted_at', 'NULL');
      $result = $segment->save();
    } else {
      $result = false;
    }
    wp_send_json($result);
  }

  function delete($data = array()) {
    $segment = Segment::findOne($data['id']);
    $confirm_delete = filter_var($data['confirm'], FILTER_VALIDATE_BOOLEAN);
    if($segment !== false) {
      if($confirm_delete) {
        $segment->delete();
        $result = true;
      } else {
        $segment->set_expr('deleted_at', 'NOW()');
        $result = $segment->save();
      }
    } else {
      $result = false;
    }
    wp_send_json($result);
  }

  function duplicate($id) {
    $result = Segment::duplicate($id);
    wp_send_json($result);
  }

  function bulk_action($data = array()) {
    $bulk_action = new Listing\BulkAction(
      '\MailPoet\Models\Segment',
      $data
    );

    wp_send_json($bulk_action->apply());
  }
}

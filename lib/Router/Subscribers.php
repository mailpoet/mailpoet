<?php
namespace MailPoet\Router;

use MailPoet\Listing;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;

if(!defined('ABSPATH')) exit;

class Subscribers {
  function __construct() {
  }

  function get($data = array()) {
    $id = (isset($data['id']) ? (int) $data['id'] : 0);

    $subscriber = Subscriber::findOne($id);
    if($subscriber === false) {
      wp_send_json(false);
    } else {
      wp_send_json($subscriber->asArray());
    }
  }

  function listing($data = array()) {
    $listing = new Listing\Handler(
      '\MailPoet\Models\Subscriber',
      $data
    );

    $listing_data = $listing->get();

    // fetch segments relations for each returned item
    foreach($listing_data['items'] as &$item) {
      $segments = SubscriberSegment::select('segment_id')
        ->where('subscriber_id', $item['id'])
        ->findMany();
      $item['segments'] = array_map(function($relation) {
        return $relation->segment_id;
      }, $segments);
    }

    wp_send_json($listing_data);
  }

  function getAll() {
    $collection = Subscriber::findArray();
    wp_send_json($collection);
  }

  function save($data = array()) {
    $result = Subscriber::createOrUpdate($data);
    wp_send_json($result);
  }

  function restore($id) {
    $subscriber = Subscriber::findOne($id);
    if($subscriber !== false) {
      $subscriber->set_expr('deleted_at', 'NULL');
      $result = array('subscribers' => (int)$subscriber->save());
    } else {
      $result = false;
    }
    wp_send_json($result);
  }

  function delete($data = array()) {
    $subscriber = Subscriber::findOne($data['id']);
    $confirm_delete = filter_var($data['confirm'], FILTER_VALIDATE_BOOLEAN);
    if($subscriber !== false) {
      if($confirm_delete) {
        $subscriber->delete();
        $result = array('subscribers' => 1);
      } else {
        $subscriber->set_expr('deleted_at', 'NOW()');
        $result = array('subscribers' => (int)$subscriber->save());
      }
    } else {
      $result = false;
    }
    wp_send_json($result);
  }

  function bulk_action($data = array()) {
    $bulk_action = new Listing\BulkAction(
      '\MailPoet\Models\Subscriber',
      $data
    );

    wp_send_json($bulk_action->apply());
  }
}

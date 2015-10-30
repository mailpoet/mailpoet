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
      // avatar
      $item['avatar_url'] = get_avatar_url($item['email'], array(
        'size' => 32
      ));

      // subscriber's segments
      $relations = SubscriberSegment::select('segment_id')
        ->where('subscriber_id', $item['id'])
        ->findMany();
      $item['segments'] = array_map(function($relation) {
        return $relation->segment_id;
      }, $relations);
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
    $result = false;

    $subscriber = Subscriber::findOne($id);
    if($subscriber !== false) {
      $result = $subscriber->restore();
    }

    wp_send_json($result);
  }

  function trash($id) {
    $result = false;

    $subscriber = Subscriber::findOne($id);
    if($subscriber !== false) {
      $result = $subscriber->trash();
    }

    wp_send_json($result);
  }

  function delete($id) {
    $result = false;

    $subscriber = Subscriber::findOne($id);
    if($subscriber !== false) {
      $subscriber->delete();
      $result = 1;
    }

    wp_send_json($result);
  }

  function item_action($data = array()) {
    $item_action = new Listing\ItemAction(
      '\MailPoet\Models\Segment',
      $data
    );

    wp_send_json($item_action->apply());
  }

  function bulk_action($data = array()) {
    $bulk_action = new Listing\BulkAction(
      '\MailPoet\Models\Subscriber',
      $data
    );

    wp_send_json($bulk_action->apply());
  }
}

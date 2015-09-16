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
      \Model::factory('\MailPoet\Models\Subscriber'),
      $data
    );
    wp_send_json($listing->get());
  }

  function bulk_action($data = array()) {
    $action = $data['action'];
    $selection = (isset($data['selection']) ? $data['selection'] : null);
    $listing_data = $data['listing'];

    $listing = new Listing\Handler(
      \Model::factory('\MailPoet\Models\Subscriber'),
      $listing_data
    );

    $selected = $listing->getSelection($selection);
    $subscribers = $selected->findMany();

    $result = false;
    switch($action) {
      case 'move':
        $segment_id = (isset($data['segment_id']) ? (int)$data['segment_id'] : 0);
        foreach($subscribers as $subscriber) {
          // remove subscriber from all segments
          SubscriberSegment::where('subscriber_id', $subscriber->id)->deleteMany();

          // create relation with segment
          $association = SubscriberSegment::create();
          $association->subscriber_id = $subscriber->id;
          $association->segment_id = $segment_id;
          $association->save();
        }
        $result = true;
      break;

      case 'remove':
        $segment_id = (isset($data['segment_id']) ? (int)$data['segment_id'] : 0);
        // delete relations with segment
        $subscriber_ids = $listing->getSelectionIds($selection);
        $result = SubscriberSegment::whereIn('subscriber_id', $subscriber_ids)
          ->where('segment_id', $segment_id)
          ->deleteMany();
      break;

      case 'add':
        $segment_id = (isset($data['segment_id']) ? (int)$data['segment_id'] : 0);
        foreach($subscribers as $subscriber) {
          // create relation with segment
          $association = SubscriberSegment::create();
          $association->subscriber_id = $subscriber->id;
          $association->segment_id = $segment_id;
          $association->save();
        }
        $result = true;
      break;

      case 'trash':
        // delete relations with all segments
        $subscriber_ids = $listing->getSelectionIds($selection);
        SubscriberSegment::whereIn('subscriber_id', $subscriber_ids)->deleteMany();

        $result = $selected->deleteMany();
      break;
    }

    wp_send_json($result);
  }

  function getAll() {
    $collection = Subscriber::findArray();
    wp_send_json($collection);
  }

  function save($data = array()) {
    $result = Subscriber::createOrUpdate($data);
    wp_send_json($result);
  }

  function delete($id) {
    $subscriber = Subscriber::findOne($id);
    if($subscriber !== false) {
      $result = $subscriber->delete();
    } else {
      $result = false;
    }
    wp_send_json($result);
  }
}

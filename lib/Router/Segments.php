<?php
namespace MailPoet\Router;
use \MailPoet\Models\Segment;
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
    wp_send_json($listing->get());
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
      $result = array('segments' => (int)$segment->save());
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
        $result = array('segments' => 1);
      } else {
        $segment->set_expr('deleted_at', 'NOW()');
        $result = array('segments' => (int)$segment->save());
      }
    } else {
      $result = false;
    }
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

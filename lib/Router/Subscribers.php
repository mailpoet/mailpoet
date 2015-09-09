<?php
namespace MailPoet\Router;
use \MailPoet\Models\Subscriber;
use \MailPoet\Listing;

if(!defined('ABSPATH')) exit;

class Subscribers {
  function __construct() {
  }

  function get($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : 0);

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

  function getAll() {
    $collection = Subscriber::findArray();
    wp_send_json($collection);
  }

  function save($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : 0);

    if($id > 0) {
      // update
      $model = Subscriber::findOne($id);
      $model->hydrate($data);
      $saved = $model->save();
    } else {
      // new
      $model = Subscriber::create();
      $model->hydrate($data);
      $saved = $model->save();
    }

    if($saved === false) {
      wp_send_json($model->getValidationErrors());
    } else {
      wp_send_json(true);
    }
  }

  function update($data) {

  }

  function delete($id) {

  }
}

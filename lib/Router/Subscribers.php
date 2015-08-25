<?php
namespace MailPoet\Router;
use \MailPoet\Models\Subscriber;

if(!defined('ABSPATH')) exit;

class Subscribers {
  function __construct() {
  }

  function get() {
    if(isset($_POST['data'])) {
      $data = $_POST['data'];
      $offset = (isset($data['offset']) ? (int)$data['offset'] : 0);
      $limit = (isset($data['limit']) ? (int)$data['limit'] : 50);
      $collection = Subscriber::offset($offset)->limit($limit)->find_array();
    } else {
      $collection = Subscriber::find_array();
    }
    wp_send_json($collection);
  }

  function save($args) {
    $model = Subscriber::create();
    $model->hydrate($args);
    $saved = $model->save();

    if(!$saved) {
      wp_send_json($model->getValidationErrors());
    }

    wp_send_json(true);
  }

  function update($args) {

  }

  function delete($id) {

  }
}

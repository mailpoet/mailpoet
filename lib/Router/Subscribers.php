<?php
namespace MailPoet\Router;
use \MailPoet\Models\Subscriber;

if(!defined('ABSPATH')) exit;

class Subscribers {
  function __construct() {
  }

  function get() {
    if(isset($_POST['data'])) {
      // search filter
      $search = (isset($_POST['data']['search']))
                  ? $_POST['data']['search']
                  : '';

      $collection = Subscriber::where_like('email', '%'.$search.'%')->find_array();
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

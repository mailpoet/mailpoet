<?php
namespace MailPoet\Router;
use \MailPoet\Models\Newsletter;

if(!defined('ABSPATH')) exit;

class Newsletters {
  function __construct() {
  }

  function get() {
    $collection = Newsletter::find_array();
    wp_send_json($collection);
  }

  function save($args) {
    $model = Newsletter::create();
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

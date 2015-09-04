<?php
namespace MailPoet\Router;
use \MailPoet\Models\Segment;
use \MailPoet\Listing;

if(!defined('ABSPATH')) exit;

class Segments {
  function __construct() {
  }

  function get($data = array()) {
    $listing = new Listing\Handler(
      \Model::factory('\MailPoet\Models\Segment'),
      $data
    );
    wp_send_json($listing->get());
  }

  function getAll() {
    $collection = Segment::find_array();
    wp_send_json($collection);
  }

  function save($args) {
    $model = Segment::create();
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

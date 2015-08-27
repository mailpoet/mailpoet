<?php
namespace MailPoet\Router;
use \MailPoet\Models\Subscriber;

if(!defined('ABSPATH')) exit;

class Subscribers {
  function __construct() {
  }

  function get($data = array()) {
    $offset = (isset($data['offset']) ? (int)$data['offset'] : 0);
    $limit = (isset($data['limit']) ? (int)$data['limit'] : 50);
    $search = (isset($data['search']) ? $data['search'] : null);
    $sort_by = (isset($data['sort_by']) ? $data['sort_by'] : 'id');
    $sort_order = (isset($data['sort_order']) ? $data['sort_order'] : 'desc');

    $collection = Subscriber::{'order_by_'.$sort_order}($sort_by);

    if($search !== null) {
      $collection->where_raw(
        '(`email` LIKE ? OR `first_name` LIKE ? OR `last_name` LIKE ?)',
        array('%'.$search.'%', '%'.$search.'%', '%'.$search.'%')
      );
    }

    // filters
    $filters = array(

    );

    $collection = array(
      'count' => $collection->count(),
      'filters' => $filters,
      'items' => $collection
        ->offset($offset)
        ->limit($limit)
        ->find_array()
    );

    wp_send_json($collection);
  }

  function getAll() {
    $collection = Subscriber::find_array();
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

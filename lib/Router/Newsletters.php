<?php
namespace MailPoet\Router;
use \MailPoet\Models\Newsletter;
use \MailPoet\Mailer\Bridge;

if(!defined('ABSPATH')) exit;

class Newsletters {
  function __construct() {
  }

  function get($data = array()) {
    // pagination
    $offset = (isset($data['offset']) ? (int)$data['offset'] : 0);
    $limit = (isset($data['limit']) ? (int)$data['limit'] : 50);
    // searching
    $search = (isset($data['search']) ? $data['search'] : null);
    // sorting
    $sort_by = (isset($data['sort_by']) ? $data['sort_by'] : 'id');
    $sort_order = (isset($data['sort_order']) ? $data['sort_order'] : 'asc');
    // grouping
    $group = (isset($data['group']) ? $data['group'] : null);
    $groups = array(
      array(
        'name' => 'all',
        'label' => __('All'),
        'count' => Newsletter::count()
      )
    );

    // instantiate subscriber collection
    $collection = Newsletter::{'order_by_'.$sort_order}($sort_by);

    // handle search
    if($search !== null) {
      $collection->where_like('subject', '%'.$search.'%');
    }

    // handle filters
    $filters = array();

    // return result
    $collection = array(
      'count' => $collection->count(),
      'filters' => $filters,
      'groups' => $groups,
      'items' => $collection
        ->offset($offset)
        ->limit($limit)
        ->find_array()
    );

    wp_send_json($collection);
  }

  function getAll() {
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

  function send($id) {
    $newsletter = Newsletter::find_one($id)->as_array();
    $subscribers = Newsletter::find_array();
    $mailer = new Bridge($newsletter, $subscribers);
    wp_send_json($mailer->send());
  }
}

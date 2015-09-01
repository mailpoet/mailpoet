<?php
namespace MailPoet\Router;
use \MailPoet\Models\Subscriber;

if(!defined('ABSPATH')) exit;

class Subscribers {
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
        'count' => Subscriber::count()
      ),
      array(
        'name' => 'subscribed',
        'label' => __('Subscribed'),
        'count' => Subscriber::where('status', Subscriber::STATE_SUBSCRIBED)->count()
      ),
      array(
        'name' => 'unconfirmed',
        'label' => __('Unconfirmed'),
        'count' => Subscriber::where('status', Subscriber::STATE_UNCONFIRMED)->count()
      ),
      array(
        'name' => 'unsubscribed',
        'label' => __('Unsubscribed'),
        'count' => Subscriber::where('status', Subscriber::STATE_UNSUBSCRIBED)->count()
      )
    );

    // instantiate subscriber collection
    $collection = Subscriber::{'order_by_'.$sort_order}($sort_by);

    // handle group
    switch($group) {
      case 'subscribed':
        $collection = $collection->where('status', Subscriber::STATE_SUBSCRIBED);
      break;

      case 'unconfirmed':
        $collection = $collection->where('status', Subscriber::STATE_UNCONFIRMED);
      break;

      case 'unsubscribed':
        $collection = $collection->where('status', Subscriber::STATE_UNSUBSCRIBED);
      break;
    }

    // handle search
    if($search !== null) {
      $collection->where_raw(
        '(`email` LIKE ? OR `first_name` LIKE ? OR `last_name` LIKE ?)',
        array('%'.$search.'%', '%'.$search.'%', '%'.$search.'%')
      );
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

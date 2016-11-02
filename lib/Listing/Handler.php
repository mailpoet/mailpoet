<?php
namespace MailPoet\Listing;

if(!defined('ABSPATH')) exit;

class Handler {
  const DEFAULT_LIMIT_PER_PAGE = 20;

  private $data = array();
  private $model = null;
  private $model_class = null;

  function __construct($model_class, $data = array()) {
    $this->table_name = $model_class::$_table;
    $this->model_class = $model_class;
    $this->model = \Model::factory($this->model_class);

    // check if sort order was specified or default to "asc"
    $sort_order = (!empty($data['sort_order'])) ? $data['sort_order'] : 'asc';
    // constrain sort order value to either be "asc" or "desc"
    $sort_order = ($sort_order === 'asc') ? 'asc' : 'desc';

    // sanitize sort by
    $sort_by = (!empty($data['sort_by']))
      ? filter_var($data['sort_by'], FILTER_SANITIZE_STRING)
      : '';

    if(empty($sort_by)) {
      $sort_by = 'id';
    }

    $this->data = array(
      // extra parameters
      'params' => (isset($data['params']) ? $data['params'] : array()),
      // pagination
      'offset' => (isset($data['offset']) ? (int)$data['offset'] : 0),
      'limit' => (isset($data['limit'])
        ? (int)$data['limit']
        : self::DEFAULT_LIMIT_PER_PAGE
      ),
      // searching
      'search' => (isset($data['search']) ? $data['search'] : null),
      // sorting
      'sort_by' => $sort_by,
      'sort_order' => $sort_order,
      // grouping
      'group' => (isset($data['group']) ? $data['group'] : null),
      // filters
      'filter' => (isset($data['filter']) ? $data['filter'] : null),
      // selection
      'selection' => (isset($data['selection']) ? $data['selection'] : null)
    );
  }

  private function setSearch() {
    if(empty($this->data['search'])) {
      return;
    }
    return $this->model->filter('search', $this->data['search']);
  }

  private function setOrder() {
    return $this->model
      ->{'order_by_'.$this->data['sort_order']}(
        $this->table_name.'.'.$this->data['sort_by']);
  }

  private function setGroup() {
    if($this->data['group'] === null) {
      return;
    }
    return $this->model->filter('groupBy', $this->data['group']);
  }

  private function setFilter() {
    if($this->data['filter'] === null) {
      return;
    }
    $this->model = $this->model->filter('filterBy', $this->data['filter']);
  }

  function getSelection() {
    if(method_exists($this->model_class, 'listingQuery')) {
      $custom_query = call_user_func_array(
        array($this->model_class, 'listingQuery'),
        array($this->data)
      );
      if(!empty($this->data['selection'])) {
        $custom_query->whereIn($this->table_name.'.id', $this->data['selection']);
      }
      return $custom_query;
    } else {
      $this->setFilter();
      $this->setGroup();
      $this->setSearch();

      if(!empty($this->data['selection'])) {
        $this->model->whereIn($this->table_name.'.id', $this->data['selection']);
      }
      return $this->model;
    }
  }

  function get() {
    // get groups
    $groups = array();
    if(method_exists($this->model_class, 'groups')) {
      $groups = call_user_func_array(
        array($this->model_class, 'groups'),
        array($this->data)
      );
    }

    // get filters
    $filters = array();
    if(method_exists($this->model_class, 'filters')) {
      $filters = call_user_func_array(
        array($this->model_class, 'filters'),
        array($this->data)
      );
    }

    // get items and total count
    if(method_exists($this->model_class, 'listingQuery')) {
      $custom_query = call_user_func_array(
        array($this->model_class, 'listingQuery'),
        array($this->data)
      );

      $count = $custom_query->count();

      $items = $custom_query
        ->offset($this->data['offset'])
        ->limit($this->data['limit'])
        ->{'order_by_'.$this->data['sort_order']}(
          $this->table_name.'.'.$this->data['sort_by']
        )
        ->findMany();
    } else {
      $this->setFilter();
      $this->setGroup();
      $this->setSearch();
      $this->setOrder();

      $count = $this->model->count();

      $items = $this->model
        ->offset($this->data['offset'])
        ->limit($this->data['limit'])
        ->findMany();
    }

    return array(
      'count' => $count,
      'filters' => $filters,
      'groups' => $groups,
      'items' => $items
    );
  }
}
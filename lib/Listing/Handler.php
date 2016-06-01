<?php
namespace MailPoet\Listing;

if(!defined('ABSPATH')) exit;

class Handler {
  const DEFAULT_LIMIT_PER_PAGE = 20;

  private $data = array();
  private $model = null;
  private $model_class = null;
  private $query_function = false;

  function __construct($model_class, $data = array(), $query_function = false) {
    $this->table_name = $model_class::$_table;
    $this->model_class = $model_class;
    $this->model = \Model::factory($this->model_class);

    if($query_function !== false) {
      // execute query function to filter results
      $query_function($this->model);

      // store query function for later use with groups/filters
      $this->query_function = $query_function;
    }

    $this->data = array(
      // pagination
      'offset' => (isset($data['offset']) ? (int)$data['offset'] : 0),
      'limit' => (isset($data['limit'])
        ? (int)$data['limit']
        : self::DEFAULT_LIMIT_PER_PAGE
      ),
      // searching
      'search' => (isset($data['search']) ? $data['search'] : null),
      // sorting
      'sort_by' => (isset($data['sort_by']) ? $data['sort_by'] : 'id'),
      'sort_order' => (isset($data['sort_order']) ? $data['sort_order'] : 'asc'),
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
    if(!empty($this->data['selection'])) {
      $this->model->whereIn($this->table_name.'.id', $this->data['selection']);
    }
    return $this->model;
  }

  function getSelectionIds() {
    $models = $this->getSelection()
      ->select('id')
      ->findArray();

    return array_map(function($model) {
      return (int)$model['id'];
    }, $models);
  }

  function getData() {
    // get groups
    $groups = call_user_func_array(
      array($this->model_class, 'groups'),
      array($this->query_function)
    );

    // get filters
    $filters = call_user_func_array(
      array($this->model_class, 'filters'),
      array($this->query_function, $this->data['group'])
    );

    $this->setGroup();
    $this->setFilter();
    $this->setSearch();
    $this->setOrder();

    $count = $this->model->count();

    $items = $this->model
      ->offset($this->data['offset'])
      ->limit($this->data['limit'])
      ->findMany();


    return array(
      'count' => $count,
      'filters' => $filters,
      'groups' => $groups,
      'items' => $items
    );
  }
}
<?php
namespace MailPoet\Listing;

if(!defined('ABSPATH')) exit;

class Handler {

  private $data = array();
  private $model = null;

  function __construct($model_class, $data = array()) {
    $class = new \ReflectionClass($model_class);
    $this->table_name = $class->getStaticPropertyValue('_table');

    $this->model = \Model::factory($model_class);

    $this->data = array(
      // pagination
      'offset' => (isset($data['offset']) ? (int)$data['offset'] : 0),
      'limit' => (isset($data['limit']) ? (int)$data['limit'] : 50),
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

    $this->model = $this->setFilter();
    $this->setSearch();
    $this->setGroup();
    $this->setOrder();
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
      return $this->model;
    }
    return $this->model->filter('filterBy', $this->data['filter']);
  }

  function getSelection() {
    if(!empty($this->data['selection'])) {
      $this->model->whereIn('id', $this->data['selection']);
    }
    return $this->model;
  }

  function count() {
    return (int)$this->model->count();
  }

  function getSelectionIds() {
    $models = $this->getSelection()
      ->select('id')
      ->findArray();

    return array_map(function($model) {
      return (int)$model['id'];
    }, $models);
  }

  function get() {
    $items = $this->model;
    if($this->data['limit'] > 0) {
      $items = $items
        ->offset($this->data['offset'])
        ->limit($this->data['limit']);
    } else
    $items = $items->findArray();

    return array(
      'count' => $this->model->count(),
      'filters' => $this->model->filter('filters'),
      'groups' => $this->model->filter('groups'),
      'items' => $items
    );
  }
}
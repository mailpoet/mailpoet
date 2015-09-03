<?php
namespace MailPoet\Listing;

if(!defined('ABSPATH')) exit;

class Handler {

  private $data = array();
  private $model = null;

  function __construct($model, $data = array()) {
    $this->model = $model;
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
      'group' => (isset($data['group']) ? $data['group'] : null)
    );

    $this->setSearch();
    $this->setOrder();
    $this->setGroup();
  }

  private function setSearch() {
    if($this->data['search'] === null) {
      return;
    }
    return $this->model->filter('search', $this->data['search']);
  }

  private function setOrder() {
    return $this->model
      ->{'order_by_'.$this->data['sort_order']}($this->data['sort_by']);
  }

  private function setGroup() {
    if($this->data['group'] === null) {
      return;
    }
    return $this->model->filter('group', $this->data['group']);
  }

  function get() {
    return array(
      'count' => $this->model->count(),
      'filters' => [],
      'groups' => $this->model->filter('groups'),
      'items' => $this->model
        ->offset($this->data['offset'])
        ->limit($this->data['limit'])
        ->find_array()
    );
  }
}
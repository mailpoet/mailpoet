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
    if(empty($this->data['search'])) {
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

  function getSelection($ids = array()) {
    if(!empty($ids)) {
      $this->model->whereIn('id', $ids);
    }
    return $this->model;
  }

  function getSelectionIds($ids = array()) {
    $subscribers = $this->getSelection($ids)->select('id')->findMany();
    return array_map(function($subscriber) {
      return (int)$subscriber->id;
    }, $subscribers);
  }

  function get() {
    return array(
      'count' => $this->model->count(),
      'filters' => [],
      'groups' => $this->model->filter('groups'),
      'items' => $this->model
        ->offset($this->data['offset'])
        ->limit($this->data['limit'])
        ->findArray()
    );
  }
}
<?php
namespace MailPoet\Listing;

if(!defined('ABSPATH')) exit;

class BulkAction {
  private $listing = null;
  private $data = null;
  private $model = null;

  function __construct($model, $data) {
    $this->model = $model;
    $this->data = $data;

    $this->listing = new Handler(
      \Model::factory($this->model),
      $this->data['listing']
    );
    return $this;
  }

  function apply() {
    return call_user_func_array(
      array($this->model, $this->data['action']),
      array($this->listing, $this->data)
    );
  }
}
<?php
namespace MailPoet\Listing;

if(!defined('ABSPATH')) exit;

class BulkAction {
  private $listing = null;
  private $data = null;
  private $model_class = null;

  function __construct($model_class, $data) {
    $this->model_class = $model_class;
    $this->data = $data;

    $this->listing = new Handler(
      $this->model_class,
      $this->data['listing']
    );
    return $this;
  }

  function apply() {
    return call_user_func_array(
      array($this->model_class, $this->data['action']),
      array($this->listing, $this->data)
    );
  }
}
<?php
namespace MailPoet\Listing;

if(!defined('ABSPATH')) exit;

class BulkAction {
  private $listing = null;
  private $action = null;
  private $data = null;
  private $model_class = null;

  function __construct($model_class, $data) {
    $this->action = $data['action'];
    unset($data['action']);
    $this->data = $data;
    $this->model_class = $model_class;
    $this->listing = new Handler(
      $model_class,
      $this->data['listing']
    );
    return $this;
  }

  function apply() {
    $bulk_action_method = 'bulk'.ucfirst($this->action);

    if(!method_exists($this->model_class, $bulk_action_method)) {
      throw new \Exception(
        $this->model_class. ' has no method "'.$bulk_action_method.'"'
      );
    }

    return call_user_func_array(
      array($this->model_class, $bulk_action_method),
      array($this->listing->getSelection(), $this->data)
    );
  }
}

<?php
namespace MailPoet\Listing;

if(!defined('ABSPATH')) exit;

class ItemAction {
  private $model = null;
  private $action = null;
  private $data = null;

  function __construct($model_class, $data) {
    $id = (int)$data['id'];
    unset($data['id']);
    $this->action = $data['action'];
    unset($data['action']);
    $this->model = $model_class::findOne($id);
    if(!empty($data)) {
      $this->data = $data;
    }
    return $this;
  }

  function apply() {
    if($this->data === null) {
      return call_user_func_array(
        array($this->model, $this->action),
        array()
      );
    } else {
      return call_user_func_array(
        array($this->model, $this->action),
        array($this->data)
      );
    }
  }
}
<?php
namespace MailPoet\Models;

if (!defined('ABSPATH')) exit;

class Model extends \RedBean_SimpleModel {

  private $errors = array();

  public function error($field, $text) {
    $this->errors[$field] = $text;
  }

  public function getErrors() {
    return $this->errors;
  }

  public function update() {
    // reset the errors array
    $this->errors = array();

    // begin transaction before the update
    $this->bean->begin();
  }

  public function after_update() {
    if(count($this->errors) > 0) {
      $this->bean->rollback();
      throw new Exception('validation_failed');
    }
  }
}
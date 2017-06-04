<?php

namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class ModelValidator extends \Sudzy\Engine {
  public $validators;

  function __construct() {
    parent::__construct();
    $this->validators = array(
      'validEmail' => 'validateEmail'
    );
    $this->setupValidators();
  }

  private function setupValidators() {
    $_this = $this;
    foreach($this->validators as $validator => $action) {
      $this->addValidator($validator, function($params) use ($action, $_this) {
        return call_user_func(array($this, $action), $params);
      });
    }
  }

  function validateEmail($email) {
    return is_email($email) !== false;
  }
}
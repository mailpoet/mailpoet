<?php

namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class ModelValidator extends \Sudzy\Engine {
  public $validators;

  function __construct() {
    parent::__construct();
    $this->validators = array(
      'validEmail' => 'validateEmail',
      'validRenderedNewsletterBody' => 'validateRenderedNewsletterBody'
    );
    $this->setupValidators();
  }

  private function setupValidators() {
    $_this = $this;
    foreach($this->validators as $validator => $action) {
      $this->addValidator($validator, function($params) use ($action, $_this) {
        return call_user_func(array($_this, $action), $params);
      });
    }
  }

  function validateEmail($email) {
    return is_email($email) !== false;
  }

  function validateRenderedNewsletterBody($newsletter_body) {
    $newsletter_body = (!is_serialized($newsletter_body)) ?
      $newsletter_body :
      unserialize($newsletter_body);
    return (is_null($newsletter_body) || (is_array($newsletter_body) && !empty($newsletter_body['html']) && !empty($newsletter_body['text'])));
  }
}
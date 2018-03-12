<?php

namespace MailPoet\Models;

use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class ModelValidator extends \Sudzy\Engine {
  public $validators;

  const EMAIL_MIN_LENGTH = 6;
  const EMAIL_MAX_LENGTH = 150;

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
    $permitted_length = (strlen($email) >= self::EMAIL_MIN_LENGTH && strlen($email) <= self::EMAIL_MAX_LENGTH);
    $valid_email = is_email($email) !== false && parent::_isEmail($email, null);
    return ($permitted_length && $valid_email);
  }

  function validateRenderedNewsletterBody($newsletter_body) {
    if(is_serialized($newsletter_body)) {
      $newsletter_body = unserialize($newsletter_body);
    } else if(Helpers::isJson($newsletter_body)) {
      $newsletter_body = json_decode($newsletter_body, true);
    }
    return (is_null($newsletter_body) || (is_array($newsletter_body) && !empty($newsletter_body['html']) && !empty($newsletter_body['text'])));
  }
}
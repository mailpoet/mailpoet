<?php
namespace MailPoet\Models;

if (!defined('ABSPATH')) exit;

class Newsletter extends Model {
  public static $_table = MP_NEWSLETTERS_TABLE;

  function __construct() {
    parent::__construct();

    $this->addValidations('subject', array(
      'required' => 'subject_is_blank',
      'isString' => 'subject_is_not_string'
    ));
    $this->addValidations('body', array(
      'required' => 'body_is_blank',
      'isString' => 'body_is_not_string'
    ));
  }
}

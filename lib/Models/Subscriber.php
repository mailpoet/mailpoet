<?php
namespace MailPoet\Models;

if (!defined('ABSPATH')) {
  exit;
}

class Subscriber extends Model {
  public static $_table = MP_SUBSCRIBERS_TABLE;

  function __construct() {
    $this->addValidations(
        'email',
        array('required' => "validation_email_blank",
              'isEmail'  => "validation_email_invalid"
        )
    );
    $this->addValidations(
        'first_name',
        array('required'    => "validation_first_name_blank",
              'minLength|2' => "validation_first_name_length"
        )
    );
    $this->addValidations(
        'last_name',
        array('required'    => "validation_last_name_blank",
              'minLength|2' => "validation_last_name_length"
        )
    );
  }

}

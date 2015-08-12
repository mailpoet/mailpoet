<?php
namespace MailPoet\Models;

if (!defined('ABSPATH')) exit;

class Subscriber extends Model {
  public static $_table = MP_SUBSCRIBERS_TABLE;

  function __construct() {
    $this->addValidations('email', array(
        'required' => "email_is_blank",
        'isEmail'  => "email_is_invalid"
    ));
    $this->addValidations('first_name', array(
        'required'    => "first_name_is_blank",
        'minLength|2' => "first_name_is_short"
    ));
    $this->addValidations('last_name', array(
        'required'    => "last_name_is_blank",
        'minLength|2' => "last_name_is_short"
    ));
  }

}

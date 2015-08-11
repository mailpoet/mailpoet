<?php
namespace MailPoet\Models;

if (!defined('ABSPATH')) {
  exit;
}

class Setting extends Model {
  public static $_table = MP_SETTINGS_TABLE;

  function __construct() {
    parent::__construct();

    $this->addValidations(
        "name",
        array("required"    => "validation_option_name_blank",
              "isString"    => "validation_option_name_string",
              "minLength|2" => "validation_option_name_length"
        )
    );
    $this->addValidations(
        "value",
        array("required"    => "validation_option_value_blank",
              "isString"    => "validation_option_value_string",
              "minLength|2" => "validation_option_value_length"
        )
    );

  }

}

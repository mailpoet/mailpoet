<?php
namespace MailPoet\Models;

if (!defined('ABSPATH')) exit;

class Setting extends Model {
  public static $_table = MP_SETTINGS_TABLE;

  function __construct() {
    parent::__construct();

    $this->addValidations("name", array(
      "required"    => "name_is_blank",
      "isString"    => "name_is_not_string"
    ));
    $this->addValidations("value", array(
      "required"    => "value_is_blank",
      "isString"    => "value_is_not_string"
    ));
  }

  public static function createOrUpdate($model) {
    $exists = self::where('name', $model['name'])
      ->find_one();

    if($exists === false) {
      $new_model = self::create();
      $new_model->hydrate($model);
      return $new_model->save();
    }

    $exists->value = $model['value'];
    return $exists->save();
  }
}

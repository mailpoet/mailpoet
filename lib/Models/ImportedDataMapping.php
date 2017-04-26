<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class ImportedDataMapping extends Model {
  public static $_table = MP_IMPORTED_DATA_MAPPING_TABLE;

  static function create($data = array()) {
    $relation = parent::create();
    $relation->hydrate($data);
    return $relation->save();
  }

}
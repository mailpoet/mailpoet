<?php
namespace MailPoet\Models;

if (!defined('ABSPATH')) exit;

class MappingToExternalEntities extends Model {
  public static $_table = MP_MAPPING_TO_EXTERNAL_ENTITIES_TABLE;

  static function create($data = []) {
    $relation = parent::create();
    $relation->hydrate($data);
    return $relation->save();
  }

}

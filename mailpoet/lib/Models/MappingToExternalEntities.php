<?php

namespace MailPoet\Models;

class MappingToExternalEntities extends Model {
  public static $_table = MP_MAPPING_TO_EXTERNAL_ENTITIES_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration

  public static function create($data = []) {
    $relation = parent::create();
    $relation->hydrate($data);
    return $relation->save();
  }
}

<?php

namespace MailPoet\Doctrine;

use MailPoet\Config\Env;
use MailPoetVendor\Doctrine\ORM\Mapping\ClassMetadata;
use MailPoetVendor\Doctrine\ORM\Mapping\ClassMetadataFactory;
use MailPoetVendor\Doctrine\ORM\Mapping\ClassMetadataInfo;

// Taken from Doctrine docs (see link bellow) but implemented in metadata factory instead of an event
// because we need to add prefix at runtime, not at metadata dump (which is included in builds).
// @see https://www.doctrine-project.org/projects/doctrine-orm/en/2.5/cookbook/sql-table-prefixes.html
class TablePrefixMetadataFactory extends ClassMetadataFactory {
  /** @var string */
  private $prefix;

  /** @var array */
  private $prefixed_map = [];

  function __construct() {
    if (Env::$db_prefix === null) {
      throw new \RuntimeException('DB table prefix not initialized');
    }
    $this->prefix = Env::$db_prefix;
  }

  function getMetadataFor($class_name) {
    $class_metadata = parent::getMetadataFor($class_name);
    if (isset($this->prefixed_map[$class_metadata->getName()])) {
      return $class_metadata;
    }

    // prefix tables only after they are saved to cache so the prefix does not get included in cache
    // (getMetadataFor can call itself recursively but it saves to cache only after the recursive calls)
    $is_cached = $this->getCacheDriver()->contains($class_metadata->getName() . $this->cacheSalt);
    if ($class_metadata instanceof ClassMetadata && $is_cached) {
      $this->addPrefix($class_metadata);
      $this->prefixed_map[$class_metadata->getName()] = true;
    }
    return $class_metadata;
  }

  function addPrefix(ClassMetadata $class_metadata) {
    if (!$class_metadata->isInheritanceTypeSingleTable() || $class_metadata->getName() === $class_metadata->rootEntityName) {
      $class_metadata->setPrimaryTable([
        'name' => $this->prefix . $class_metadata->getTableName(),
      ]);
    }

    foreach ($class_metadata->getAssociationMappings() as $field_name => $mapping) {
      if ($mapping['type'] === ClassMetadataInfo::MANY_TO_MANY && $mapping['isOwningSide']) {
        $mapped_table_name = $mapping['joinTable']['name'];
        $class_metadata->associationMappings[$field_name]['joinTable']['name'] = $this->prefix . $mapped_table_name;
      }
    }
  }
}

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

  function getMetadataFor($className) {
    $classMetadata = parent::getMetadataFor($className);
    if ($classMetadata instanceof ClassMetadata && !isset($this->prefixed_map[$classMetadata->getName()])) {
      $this->addPrefix($classMetadata);
      $this->prefixed_map[$classMetadata->getName()] = true;
    }
    return $classMetadata;
  }

  function addPrefix(ClassMetadata $classMetadata) {
    if (!$classMetadata->isInheritanceTypeSingleTable() || $classMetadata->getName() === $classMetadata->rootEntityName) {
      $classMetadata->setPrimaryTable([
        'name' => $this->prefix . $classMetadata->getTableName(),
      ]);
    }

    foreach ($classMetadata->getAssociationMappings() as $fieldName => $mapping) {
      if ($mapping['type'] == ClassMetadataInfo::MANY_TO_MANY && $mapping['isOwningSide']) {
        $mappedTableName = $mapping['joinTable']['name'];
        $classMetadata->associationMappings[$fieldName]['joinTable']['name'] = $this->prefix . $mappedTableName;
      }
    }
  }
}

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
  private $prefixedMap = [];

  public function __construct() {
    if (Env::$dbPrefix === null) {
      throw new \RuntimeException('DB table prefix not initialized');
    }
    $this->prefix = Env::$dbPrefix;
  }

  public function getMetadataFor($className) {
    $classMetadata = parent::getMetadataFor($className);
    if (isset($this->prefixedMap[$classMetadata->getName()])) {
      return $classMetadata;
    }

    // prefix tables only after they are saved to cache so the prefix does not get included in cache
    // (getMetadataFor can call itself recursively but it saves to cache only after the recursive calls)
    $isCached = ($cache = $this->getCacheDriver()) ? $cache->contains($classMetadata->getName() . $this->cacheSalt) : false;
    if ($classMetadata instanceof ClassMetadata && $isCached) {
      $this->addPrefix($classMetadata);
      $this->prefixedMap[$classMetadata->getName()] = true;
    }
    return $classMetadata;
  }

  public function addPrefix(ClassMetadata $classMetadata) {
    if (!$classMetadata->isInheritanceTypeSingleTable() || $classMetadata->getName() === $classMetadata->rootEntityName) {
      $classMetadata->setPrimaryTable([
        'name' => $this->prefix . $classMetadata->getTableName(),
      ]);
    }

    foreach ($classMetadata->getAssociationMappings() as $fieldName => $mapping) {
      if ($mapping['type'] === ClassMetadataInfo::MANY_TO_MANY && $mapping['isOwningSide']) {
        $mappedTableName = $mapping['joinTable']['name'];
        $classMetadata->associationMappings[$fieldName]['joinTable']['name'] = $this->prefix . $mappedTableName;
      }
    }
  }
}

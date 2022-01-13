<?php declare(strict_types = 1);

//require_once __DIR__ . '/../../vendor/autoload.php';

use MailPoet\Doctrine\Annotations\AnnotationReaderProvider;
use MailPoet\Doctrine\ConfigurationFactory;
use MailPoet\Doctrine\ConnectionFactory;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoetVendor\Doctrine\ORM\Mapping\Driver\AnnotationDriver;

$annotationReaderProvider = new AnnotationReaderProvider();
$annotationReader = $annotationReaderProvider->getAnnotationReader();
$configuration = (new ConfigurationFactory($annotationReaderProvider, false))->createConfiguration();
$configuration->setMetadataDriverImpl(
  new class($annotationReader, [ConfigurationFactory::ENTITY_DIR]) extends AnnotationDriver {
    // Returning 'isTransient' = true means 'do not try to load Doctrine metadata' (which is true for most classes).
    // Here we override the method to check agains the ENTITY_DIR since phpstan-doctrine seems to sometimes pass
    // non-entity classes with non-Doctrine annotations to this methods. It may be fixed in future versions.
    public function isTransient($className) {
      $reflection = new ReflectionClass($className);
      if ($reflection && $reflection->getFileName()) {
        $entityDirRealpath = realpath(ConfigurationFactory::ENTITY_DIR);
        $fileRealpath = realpath($reflection->getFileName());
        if (substr($fileRealpath, 0, strlen($entityDirRealpath)) !== $entityDirRealpath) {
          return true;
        }
      }
      return parent::isTransient($className);
    }
  }
);
$platformClass = ConnectionFactory::PLATFORM_CLASS;
return EntityManager::create([
  'driver' => ConnectionFactory::DRIVER,
  'platform' => new $platformClass,
], $configuration);

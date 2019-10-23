<?php

namespace MailPoet\Doctrine;

use MailPoet\Doctrine\Annotations\AnnotationReaderProvider;
use MailPoetVendor\Doctrine\Common\Cache\ArrayCache;
use MailPoetVendor\Doctrine\Common\Proxy\AbstractProxyFactory;
use MailPoetVendor\Doctrine\ORM\Configuration;
use MailPoetVendor\Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use MailPoetVendor\Doctrine\ORM\Mapping\Driver\PHPDriver;
use MailPoetVendor\Doctrine\ORM\Mapping\UnderscoreNamingStrategy;

class ConfigurationFactory {
  const ENTITY_DIR = __DIR__ . '/../Entities';
  const METADATA_DIR = __DIR__ . '/../../generated/doctrine-metadata';
  const PROXY_DIR = __DIR__ . '/../../generated/doctrine-proxies';
  const PROXY_NAMESPACE = 'MailPoetDoctrineProxies';

  /** @var bool */
  private $is_dev_mode;

  /** @var AnnotationReaderProvider */
  private $annotation_reader_provider;

  function __construct($is_dev_mode = null, AnnotationReaderProvider $annotation_reader_provider) {
    $this->is_dev_mode = $is_dev_mode === null ? WP_DEBUG : $is_dev_mode;
    $this->annotation_reader_provider = $annotation_reader_provider;
  }

  function createConfiguration() {
    $configuration = new Configuration();
    $configuration->setNamingStrategy(new UnderscoreNamingStrategy());

    $this->configureMetadata($configuration);
    $this->configureProxies($configuration);
    $this->configureCache($configuration);
    return $configuration;
  }

  private function configureMetadata(Configuration $configuration) {
    $configuration->setClassMetadataFactoryName(TablePrefixMetadataFactory::class);

    // annotation reader exists only in dev environment, on production cache is pre-generated
    $annotation_reader = $this->annotation_reader_provider->getAnnotationReader();
    if ($annotation_reader) {
      $configuration->setMetadataDriverImpl(new AnnotationDriver($annotation_reader, [self::ENTITY_DIR]));
    } else {
      // Should never be called but Doctrine requires having driver set
      $configuration->setMetadataDriverImpl(new PHPDriver([]));
    }

    // metadata cache (for production cache is pre-generated at build time)
    $is_read_only = !$annotation_reader;
    $metadata_storage = new MetadataCache(self::METADATA_DIR, $is_read_only);
    $configuration->setMetadataCacheImpl($metadata_storage);
  }

  private function configureProxies(Configuration $configuration) {
    $configuration->setProxyDir(self::PROXY_DIR);
    $configuration->setProxyNamespace(self::PROXY_NAMESPACE);
    $configuration->setAutoGenerateProxyClasses(
      $this->is_dev_mode
        ? AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS
        : AbstractProxyFactory::AUTOGENERATE_NEVER
    );
  }

  private function configureCache(Configuration $configuration) {
    $cache = new ArrayCache();
    $configuration->setQueryCacheImpl($cache);
    $configuration->setResultCacheImpl($cache);
  }
}

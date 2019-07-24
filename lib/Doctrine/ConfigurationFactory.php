<?php

namespace MailPoet\Doctrine;

use MailPoetVendor\Doctrine\Common\Annotations\SimpleAnnotationReader;
use MailPoetVendor\Doctrine\Common\Cache\ArrayCache;
use MailPoetVendor\Doctrine\Common\Proxy\AbstractProxyFactory;
use MailPoetVendor\Doctrine\ORM\Configuration;
use MailPoetVendor\Doctrine\ORM\Mapping\UnderscoreNamingStrategy;

class ConfigurationFactory {
  const ENTITY_DIR = __DIR__ . '/../Entities';
  const METADATA_DIR = __DIR__ . '/../../generated/doctrine-metadata';
  const PROXY_DIR = __DIR__ . '/../../generated/doctrine-proxies';
  const PROXY_NAMESPACE = 'MailPoetDoctrineProxies';

  /** @var bool */
  private $is_dev_mode;

  function __construct($is_dev_mode = null) {
    $this->is_dev_mode = $is_dev_mode === null ? WP_DEBUG : $is_dev_mode;
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

    // metadata cache (for production cache is pre-generated at build time)
    $metadata_storage = new MetadataCache(self::METADATA_DIR);
    $configuration->setMetadataCacheImpl($metadata_storage);

    // register annotation reader if doctrine/annotations package is installed
    // (i.e. in dev environment, on production metadata is dumped in the build)
    if (class_exists(SimpleAnnotationReader::class)) {
      $configuration->setMetadataDriverImpl($configuration->newDefaultAnnotationDriver([self::ENTITY_DIR]));
    }
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

<?php

namespace MailPoet\Doctrine;

use MailPoet\Config\Env;
use MailPoetVendor\Doctrine\Common\Annotations\SimpleAnnotationReader;
use MailPoetVendor\Doctrine\Common\Cache\ArrayCache;
use MailPoetVendor\Doctrine\Common\Cache\FilesystemCache;
use MailPoetVendor\Doctrine\Common\Proxy\AbstractProxyFactory;
use MailPoetVendor\Doctrine\ORM\Configuration;
use MailPoetVendor\Doctrine\ORM\Mapping\UnderscoreNamingStrategy;

class ConfigurationFactory {
  const ENTITY_DIR = __DIR__ . '/../Doctrine/Entities';
  const METADATA_DIR = __DIR__ . '/../../generated/doctrine-metadata';
  const PROXY_DIR = __DIR__ . '/../../generated/doctrine-proxies';
  const PROXY_NAMESPACE = 'MailPoetDoctrineProxies';

  /** @var bool */
  private $is_dev_mode;

  function __construct() {
    $this->is_dev_mode = WP_DEBUG;
  }

  function createConfiguration() {
    $configuration = new Configuration();
    $configuration->setNamingStrategy(new UnderscoreNamingStrategy());

    $this->configureMetadata($configuration);
    $this->configureProxies($configuration);
    $this->configureCache($configuration);

    if (!$this->is_dev_mode) {
      $configuration->ensureProductionSettings();
    }
    return $configuration;
  }

  private function configureMetadata(Configuration $configuration) {
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
    if ($this->is_dev_mode) {
      $cache = new ArrayCache();
    } else {
      $cache_dir = Env::$cache_path . '/doctrine';
      $cache = new FilesystemCache($cache_dir);
      $cache->setNamespace('mp3-' . md5($cache_dir) . '-');
    }

    $configuration->setQueryCacheImpl($cache);
    $configuration->setResultCacheImpl($cache);
  }
}

<?php

namespace MailPoet\Test\Config;

use MailPoet\Doctrine\ConfigurationFactory;
use MailPoet\Doctrine\MetadataCache;
use MailPoet\Doctrine\TablePrefixMetadataFactory;
use MailPoetVendor\Doctrine\Common\Cache\ArrayCache;
use MailPoetVendor\Doctrine\Common\Cache\FilesystemCache;
use MailPoetVendor\Doctrine\Common\Proxy\AbstractProxyFactory;
use MailPoetVendor\Doctrine\ORM\Configuration;
use MailPoetVendor\Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use MailPoetVendor\Doctrine\ORM\Mapping\UnderscoreNamingStrategy;

class ConfigurationFactoryTest extends \MailPoetTest {
  function testItSetsUpBasicOptions() {
    $configuration_factory = new ConfigurationFactory();
    $configuration = $configuration_factory->createConfiguration();

    expect($configuration)->isInstanceOf(Configuration::class);
    expect($configuration->getNamingStrategy())->isInstanceOf(UnderscoreNamingStrategy::class);

    // metadata
    expect($configuration->getClassMetadataFactoryName())->equals(TablePrefixMetadataFactory::class);
    expect($configuration->getMetadataCacheImpl())->isInstanceOf(MetadataCache::class);
    expect($configuration->getMetadataDriverImpl())->isInstanceOf(AnnotationDriver::class);

    // proxies
    expect(realpath($configuration->getProxyDir()))->equals(realpath(__DIR__ . '/../../../generated/doctrine-proxies'));
    expect($configuration->getProxyNamespace())->equals('MailPoetDoctrineProxies');
  }

  function testItSetsUpEnvironmentSpecificOptions() {
    // dev mode
    $configuration_factory = new ConfigurationFactory(true);
    $configuration = $configuration_factory->createConfiguration();
    expect($configuration->getQueryCacheImpl())->isInstanceOf(ArrayCache::class);
    expect($configuration->getResultCacheImpl())->isInstanceOf(ArrayCache::class);
    expect($configuration->getAutoGenerateProxyClasses())->equals(AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS);

    // production mode
    $configuration_factory = new ConfigurationFactory(false);
    $configuration = $configuration_factory->createConfiguration();
    expect($configuration->getQueryCacheImpl())->isInstanceOf(FilesystemCache::class);
    expect($configuration->getResultCacheImpl())->isInstanceOf(FilesystemCache::class);
    expect($configuration->getAutoGenerateProxyClasses())->equals(AbstractProxyFactory::AUTOGENERATE_NEVER);
  }
}

<?php declare(strict_types = 1);

namespace MailPoet\Test\Config;

use MailPoet\Doctrine\Annotations\AnnotationReaderProvider;
use MailPoet\Doctrine\ArrayCache;
use MailPoet\Doctrine\ConfigurationFactory;
use MailPoet\Doctrine\PSRMetadataCache;
use MailPoet\Doctrine\TablePrefixMetadataFactory;
use MailPoetVendor\Doctrine\Common\Proxy\AbstractProxyFactory;
use MailPoetVendor\Doctrine\ORM\Configuration;
use MailPoetVendor\Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use MailPoetVendor\Doctrine\ORM\Mapping\UnderscoreNamingStrategy;

class ConfigurationFactoryTest extends \MailPoetTest {
  public function testItSetsUpBasicOptions() {
    $configurationFactory = new ConfigurationFactory(new AnnotationReaderProvider(), false);
    $configuration = $configurationFactory->createConfiguration();

    expect($configuration)->isInstanceOf(Configuration::class);
    expect($configuration->getNamingStrategy())->isInstanceOf(UnderscoreNamingStrategy::class);

    // metadata
    expect($configuration->getClassMetadataFactoryName())->equals(TablePrefixMetadataFactory::class);
    expect($configuration->getMetadataCache())->isInstanceOf(PSRMetadataCache::class);
    expect($configuration->getMetadataDriverImpl())->isInstanceOf(AnnotationDriver::class);

    // cache
    expect($configuration->getQueryCacheImpl())->isInstanceOf(ArrayCache::class);
    expect($configuration->getResultCacheImpl())->isInstanceOf(ArrayCache::class);

    // proxies
    expect(realpath($configuration->getProxyDir()))->equals(realpath(__DIR__ . '/../../../generated/doctrine-proxies'));
    expect($configuration->getProxyNamespace())->equals('MailPoetDoctrineProxies');
  }

  public function testItSetsUpEnvironmentSpecificOptions() {
    // dev mode
    $configurationFactory = new ConfigurationFactory(new AnnotationReaderProvider(), true);
    $configuration = $configurationFactory->createConfiguration();
    expect($configuration->getAutoGenerateProxyClasses())->equals(AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS);

    // production mode
    $configurationFactory = new ConfigurationFactory(new AnnotationReaderProvider(), false);
    $configuration = $configurationFactory->createConfiguration();
    expect($configuration->getAutoGenerateProxyClasses())->equals(AbstractProxyFactory::AUTOGENERATE_NEVER);
  }
}

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

    verify($configuration)->instanceOf(Configuration::class);
    verify($configuration->getNamingStrategy())->instanceOf(UnderscoreNamingStrategy::class);

    // metadata
    verify($configuration->getClassMetadataFactoryName())->equals(TablePrefixMetadataFactory::class);
    verify($configuration->getMetadataCache())->instanceOf(PSRMetadataCache::class);
    verify($configuration->getMetadataDriverImpl())->instanceOf(AnnotationDriver::class);

    // cache
    verify($configuration->getQueryCacheImpl())->instanceOf(ArrayCache::class);
    verify($configuration->getResultCacheImpl())->instanceOf(ArrayCache::class);

    // proxies
    verify(realpath($configuration->getProxyDir()))->equals(realpath(__DIR__ . '/../../../generated/doctrine-proxies'));
    verify($configuration->getProxyNamespace())->equals('MailPoetDoctrineProxies');
  }

  public function testItSetsUpEnvironmentSpecificOptions() {
    // dev mode
    $configurationFactory = new ConfigurationFactory(new AnnotationReaderProvider(), true);
    $configuration = $configurationFactory->createConfiguration();
    verify($configuration->getAutoGenerateProxyClasses())->equals(AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS);

    // production mode
    $configurationFactory = new ConfigurationFactory(new AnnotationReaderProvider(), false);
    $configuration = $configurationFactory->createConfiguration();
    verify($configuration->getAutoGenerateProxyClasses())->equals(AbstractProxyFactory::AUTOGENERATE_NEVER);
  }
}

<?php

namespace MailPoet\Doctrine\Validator;

use MailPoet\Doctrine\Annotations\AnnotationReaderProvider;
use MailPoet\Doctrine\MetadataCache;
use MailPoetVendor\Symfony\Component\Validator\Mapping\Cache\DoctrineCache;
use MailPoetVendor\Symfony\Component\Validator\Validation;

class ValidatorFactory {
  const METADATA_DIR = __DIR__ . '/../../../generated/validator-metadata';

  /** @var AnnotationReaderProvider */
  private $annotationReaderProvider;

  public function __construct(AnnotationReaderProvider $annotationReaderProvider) {
    $this->annotationReaderProvider = $annotationReaderProvider;
  }

  public function createValidator() {
    $builder = Validation::createValidatorBuilder();

    // annotation reader exists only in dev environment, on production cache is pre-generated
    $annotationReader = $this->annotationReaderProvider->getAnnotationReader();
    if ($annotationReader) {
      $builder->enableAnnotationMapping($annotationReader);
    }

    // metadata cache (for production cache is pre-generated at build time)
    $isReadOnly = !$annotationReader;
    $metadataCache = new MetadataCache(self::METADATA_DIR, $isReadOnly);
    $builder->setMetadataCache(new DoctrineCache($metadataCache));

    return $builder->getValidator();
  }
}

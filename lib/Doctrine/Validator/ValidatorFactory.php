<?php

namespace MailPoet\Doctrine\Validator;

use MailPoet\Doctrine\Annotations\AnnotationReaderProvider;
use MailPoet\Doctrine\MetadataCache;
use MailPoetVendor\Symfony\Component\Validator\Mapping\Cache\DoctrineCache;
use MailPoetVendor\Symfony\Component\Validator\Validation;

class ValidatorFactory {
  const METADATA_DIR = __DIR__ . '/../../../generated/validator-metadata';

  /** @var AnnotationReaderProvider */
  private $annotation_reader_provider;

  function __construct(AnnotationReaderProvider $annotation_reader_provider) {
    $this->annotation_reader_provider = $annotation_reader_provider;
  }

  function createValidator() {
    $builder = Validation::createValidatorBuilder();

    // annotation reader exists only in dev environment, on production cache is pre-generated
    $annotation_reader = $this->annotation_reader_provider->getAnnotationReader();
    if ($annotation_reader) {
      $builder->enableAnnotationMapping($annotation_reader);
    }

    // metadata cache (for production cache is pre-generated at build time)
    $is_read_only = !$annotation_reader;
    $metadata_cache = new MetadataCache(self::METADATA_DIR, $is_read_only);
    $builder->setMetadataCache(new DoctrineCache($metadata_cache));

    return $builder->getValidator();
  }
}

<?php

namespace MailPoet\Doctrine\Annotations;

use MailPoetVendor\Doctrine\Common\Annotations\AnnotationReader;
use MailPoetVendor\Doctrine\Common\Annotations\AnnotationRegistry;
use MailPoetVendor\Doctrine\Common\Annotations\CachedReader;
use MailPoetVendor\Doctrine\Common\Cache\ArrayCache;

class AnnotationReaderProvider {
  /** @var CachedReader */
  private $annotation_reader;

  public function __construct() {
    // register annotation reader if doctrine/annotations package is installed
    // (i.e. in dev environment, on production metadata is dumped in the build)
    $read_annotations = class_exists(CachedReader::class) && class_exists(AnnotationReader::class);
    if ($read_annotations) {
      // autoload all annotation classes using registered loaders (Composer)
      // (needed for Symfony\Validator constraint annotations to be loaded)
      AnnotationRegistry::registerLoader('class_exists');
      $this->annotation_reader = new CachedReader(new AnnotationReader(), new ArrayCache());
    }
  }

  /** @return CachedReader|null */
  public function getAnnotationReader() {
    return $this->annotation_reader;
  }
}

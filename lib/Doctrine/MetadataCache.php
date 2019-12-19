<?php

namespace MailPoet\Doctrine;

use MailPoetVendor\Doctrine\Common\Cache\CacheProvider;
use MailPoetVendor\Doctrine\ORM\Mapping\ClassMetadata as DoctrineClassMetadata;
use MailPoetVendor\Symfony\Component\Validator\Mapping\ClassMetadata as ValidatorClassMetadata;
use ReflectionClass;
use ReflectionException;

// Simple filesystem-based cache storage for Doctrine Metadata.
//
// Needed because Doctrine's FilesystemCache doesn't work read-only (when metadata dumped)
// and it calls realpath() that could fail on some hostings due to filesystem permissions.
class MetadataCache extends CacheProvider {
  /** @var bool */
  private $is_dev_mode;

  /** @var string */
  private $directory;

  function __construct($dir, $is_read_only) {
    $this->is_dev_mode = defined('WP_DEBUG') && WP_DEBUG && !$is_read_only;
    $this->directory = rtrim($dir, '/\\');
    if (!file_exists($this->directory)) {
      mkdir($this->directory);
    }
  }

  protected function doFetch($id) {
    if (!$this->doContains($id)) {
      return false;
    }
    return unserialize(file_get_contents($this->getFilename($id)));
  }

  protected function doContains($id) {
    $filename = $this->getFilename($id);
    $file_exists = file_exists($filename);

    // in dev mode invalidate cache if source file has changed
    if ($file_exists && $this->is_dev_mode) {
      $class_metadata = unserialize(file_get_contents($filename));
      assert($class_metadata instanceof DoctrineClassMetadata || $class_metadata instanceof ValidatorClassMetadata);
      try {
        $reflection = new ReflectionClass($class_metadata->name);
      } catch (ReflectionException $e) {
        return false;
      }
      clearstatcache();
      return filemtime($filename) >= filemtime($reflection->getFileName());
    }

    return $file_exists;
  }

  protected function doSave($id, $data, $lifeTime = 0) {
    $filename = $this->getFilename($id);
    $result = @file_put_contents($filename, serialize($data));
    if ($result === false) {
      throw new \RuntimeException("Error while writing to '$filename'");
    }
    return true;
  }

  protected function doDelete($id) {
    @unlink($this->getFilename($id));
    return true;
  }

  protected function doFlush() {
    foreach (glob($this->directory . DIRECTORY_SEPARATOR . '*') as $filename) {
      if (is_file($filename)) {
        @unlink($filename);
      }
    }
    return true;
  }

  protected function doGetStats() {
    return null;
  }

  private function getFilename($id) {
    return $this->directory . DIRECTORY_SEPARATOR . md5($id);
  }
}

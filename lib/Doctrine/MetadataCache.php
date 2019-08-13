<?php

namespace MailPoet\Doctrine;

use MailPoetVendor\Doctrine\Common\Cache\CacheProvider;

// Simple filesystem-based cache storage for Doctrine Metadata.
//
// Needed because Doctrine's FilesystemCache doesn't work read-only (when metadata dumped)
// and it calls realpath() that could fail on some hostings due to filesystem permissions.
class MetadataCache extends CacheProvider {
  /** @var string */
  private $directory;

  function __construct($dir) {
    $this->directory = rtrim($dir, '/\\');
    @mkdir($this->directory);
  }

  protected function doFetch($id) {
    if (!$this->doContains($id)) {
      return null;
    }
    return unserialize(file_get_contents($this->getFilename($id)));
  }

  protected function doContains($id) {
    return file_exists($this->getFilename($id));
  }

  protected function doSave($id, $data, $lifeTime = 0) {
    $filename = $this->getFilename($id);
    $result = @file_put_contents($filename, serialize($data));
    if ($result === false) {
      throw new \RuntimeException("Error while writing to '$filename'");
    }
  }

  protected function doDelete($id) {
    @unlink($this->getFilename($id));
  }

  protected function doFlush() {
    foreach (glob($this->directory . DIRECTORY_SEPARATOR . '*') as $filename) {
      if (is_file($filename)) {
        @unlink($filename);
      }
    }
  }

  protected function doGetStats() {
    return null;
  }

  private function getFilename($id) {
    return $this->directory . DIRECTORY_SEPARATOR . md5($id);
  }
}

<?php

namespace MailPoet\Config;

class TwigFileSystemCache extends \MailPoetVendor\Twig\Cache\FilesystemCache {

  private $directory;

  public function __construct(
    string $directory,
    int $options = 0
  ) {
    $this->directory = \rtrim($directory, '\\/') . '/';
    parent::__construct($directory, $options);
  }

  public function generateKey(string $name, string $className): string {
    $hash = \hash('sha256', $className);
    return $this->directory . $hash[0] . $hash[1] . '/' . $hash . '.php';
  }
}

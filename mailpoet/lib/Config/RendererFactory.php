<?php

namespace MailPoet\Config;

use MailPoetVendor\Twig\Loader\FilesystemLoader as TwigFileSystem;

class RendererFactory {

  /** @var Renderer|null */
  private $renderer;

  public function getRenderer() {
    if (!$this->renderer) {
      $debugging = WP_DEBUG;
      $this->renderer = new Renderer(
        $debugging,
        Env::$cachePath,
        new TwigFileSystem(Env::$viewsPath)
      );
    }
    return $this->renderer;
  }
}

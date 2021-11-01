<?php

namespace MailPoet\PostEditorBlocks;

use MailPoet\Config\Env;
use MailPoet\WP\Functions as WPFunctions;

class NewsletterBlock {
  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function init() {
    $this->wp->registerBlockType( Env::$assetsPath . '/js/src/newsletter_block' );
  }
}

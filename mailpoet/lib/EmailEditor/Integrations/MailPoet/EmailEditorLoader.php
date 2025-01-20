<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use MailPoet\Config\Env;
use MailPoet\WP\Functions as WPFunctions;

class EmailEditorLoader {

  private WPFunctions $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function initialize() {
    $this->wp->addAction('enqueue_block_editor_assets', [$this, 'enqueueBlockEditorAssets']);
  }

  public function enqueueBlockEditorAssets() {
    $assetsParams = require Env::$assetsPath . '/dist/js/email-editor-unified/email_editor.asset.php';
    $this->wp->wpEnqueueScript(
      'mailpoet_email_editor',
      Env::$assetsUrl . '/dist/js/email-editor-unified/email_editor.js',
      $assetsParams['dependencies'],
      $assetsParams['version'],
      true
    );
  }
}

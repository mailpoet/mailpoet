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

  public function initialize(): void {
    $this->wp->addAction('mailpoet_email_editor_admin_initialized', [$this, 'initializeAdmin']);
  }

  public function initializeAdmin(): void {
    if (!$this->isEmailEditorPage()) {
      return;
    }
    $this->wp->addAction('mailpoet_is_email_editor_page', [$this, 'isEmailEditorPage']);
    $this->enqueueBlockEditorAssets();
  }

  public function isEmailEditorPage($isEditorPage = false): bool {
    if ($isEditorPage) {
      return true;
    }
    $current_screen = get_current_screen();
    return $current_screen && $current_screen->post_type === EmailEditor::MAILPOET_EMAIL_POST_TYPE && $current_screen->base === 'post'; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
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

<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use MailPoet\Config\Env;
use MailPoet\EmailEditor\Engine\Settings_Controller;
use MailPoet\WP\Functions as WPFunctions;

class EmailEditorLoader {

  private WPFunctions $wp;
  private Settings_Controller $settingsController;

  public function __construct(
    WPFunctions $wp,
    Settings_Controller $settingsController
  ) {
    $this->wp = $wp;
    $this->settingsController = $settingsController;
  }

  public function initialize(): void {
    $this->wp->addAction('mailpoet_email_editor_admin_initialized', [$this, 'initializeAdmin']);
    $this->wp->addFilter('block_editor_settings_all', [$this, 'blockEditorSettings'], 10, 2);
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

  public function blockEditorSettings($settings, $editorContext) {
    if (!$this->isEmailEditorPage()) {
      return $settings;
    }
    $controllerSettings = $this->settingsController->get_settings();
    return $controllerSettings;
  }
}

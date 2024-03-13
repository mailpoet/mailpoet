<?php declare(strict_types = 1);

namespace MailPoet\AdminPages\Pages;

use MailPoet\API\JSON\API;
use MailPoet\Config\Env;
use MailPoet\EmailEditor\Engine\SettingsController;
use MailPoet\EmailEditor\Engine\ThemeController;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor as EditorInitController;
use MailPoet\WP\Functions as WPFunctions;

class EmailEditor {
  private WPFunctions $wp;

  private SettingsController $settingsController;

  private ThemeController $themeController;

  public function __construct(
    WPFunctions $wp,
    SettingsController $settingsController,
    ThemeController $themeController
  ) {
    $this->wp = $wp;
    $this->settingsController = $settingsController;
    $this->themeController = $themeController;
  }

  public function render() {
    $postId = isset($_GET['postId']) ? intval($_GET['postId']) : 0;
    $post = $this->wp->getPost($postId);
    if (!$post instanceof \WP_Post || $post->post_type !== EditorInitController::MAILPOET_EMAIL_POST_TYPE) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      return;
    }

    $assetsParams = require_once Env::$assetsPath . '/dist/js/email-editor/email_editor.asset.php';
    $this->wp->wpEnqueueScript(
      'mailpoet_email_editor',
      Env::$assetsUrl . '/dist/js/email-editor/email_editor.js',
      $assetsParams['dependencies'],
      $assetsParams['version'],
      true
    );
    $this->wp->wpEnqueueStyle(
      'mailpoet_email_editor',
      Env::$assetsUrl . '/dist/js/email-editor/email_editor.css',
      [],
      $assetsParams['version']
    );

    $jsonAPIRoot = rtrim($this->wp->escUrlRaw(admin_url('admin-ajax.php')), '/');
    $token = $this->wp->wpCreateNonce('mailpoet_token');
    $apiVersion = API::CURRENT_VERSION;
    $currentUserEmail = $this->wp->wpGetCurrentUser()->user_email;
    $this->wp->wpLocalizeScript(
      'mailpoet_email_editor',
      'MailPoetEmailEditor',
      [
        'json_api_root' => esc_js($jsonAPIRoot),
        'api_token' => esc_js($token),
        'api_version' => esc_js($apiVersion),
        'current_wp_user_email' => esc_js($currentUserEmail),
        'editor_settings' => $this->settingsController->getSettings(),
        'editor_layout' => $this->settingsController->getLayout(),
        'editor_theme' => $this->themeController->getTheme()->get_raw_data(),
      ]
    );

    // Load CSS from Post Editor
    $this->wp->wpEnqueueStyle('wp-edit-post');
    // Load CSS for the format library - used for example in popover
    $this->wp->wpEnqueueStyle('wp-format-library');

    // Enqueue media library scripts
    $this->wp->wpEnqueueMedia();

    echo '<div id="mailpoet-email-editor" class="block-editor"></div>';
  }
}

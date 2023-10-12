<?php declare(strict_types = 1);

namespace MailPoet\AdminPages\Pages;

use MailPoet\API\JSON\API;
use MailPoet\Config\Env;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor as EditorInitController;
use MailPoet\WP\Functions as WPFunctions;

class EmailEditor {
  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function render() {
    $postId = isset($_GET['postId']) ? intval($_GET['postId']) : 0;
    $post = $this->wp->getPost($postId);
    if (!$post instanceof \WP_Post || $post->post_type !== EditorInitController::MAILPOET_EMAIL_POST_TYPE) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      return;
    }

    $assetsParams = require_once Env::$assetsPath . '/dist/js/email-editor-custom/email_editor.asset.php';
    $this->wp->wpEnqueueScript(
      'mailpoet_email_editor',
      Env::$assetsUrl . '/dist/js/email-editor-custom/email_editor.js',
      $assetsParams['dependencies'],
      $assetsParams['version'],
      true
    );
    $this->wp->wpEnqueueStyle(
      'mailpoet_email_editor',
      Env::$assetsUrl . '/dist/js/email-editor-custom/email_editor.css',
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
      ]
    );

    $this->wp->wpEnqueueStyle('wp-components');
    $this->wp->wpEnqueueStyle('wp-block-editor');
    $this->wp->wpEnqueueStyle('wp-block-editor-content');
    $this->wp->wpEnqueueStyle('wp-edit-post');
    $this->wp->wpEnqueueStyle('wp-editor');
    $this->wp->wpEnqueueStyle('wp-block-library');
    $this->wp->wpEnqueueStyle('wp-format-library');
    $this->wp->wpEnqueueStyle('wp-interface');

    echo '<div id="mailpoet-email-editor"></div>';
  }
}

<?php declare(strict_types = 1);

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Config\Env;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor as EditorInitController;
use MailPoet\WP\Functions as WPFunctions;

class EmailEditor {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    PageRenderer $pageRenderer,
    WPFunctions $wp
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->wp = $wp;
  }

  public function render() {
    $postId = isset($_GET['postId']) ? intval($_GET['postId']) : 0;
    $post = $this->wp->getPost($postId);
    if (!$post instanceof \WP_Post || $post->post_type !== EditorInitController::MAILPOET_EMAIL_POST_TYPE) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      $postId = wp_insert_post([
        'post_title' => 'New Email',
        'post_content' => '',
        'post_status' => 'draft',
        'post_author' => $this->wp->getCurrentUserId(),
        'post_type' => EditorInitController::MAILPOET_EMAIL_POST_TYPE,
      ]);
      return wp_safe_redirect(
        $this->wp->adminUrl('admin.php?page=mailpoet-email-editor&postId=' . $postId)
      );
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
    $this->wp->wpEnqueueStyle('wp-components');
    $this->wp->wpEnqueueStyle('wp-block-editor');
    $this->wp->wpEnqueueStyle('wp-block-editor-content');
    $this->wp->wpEnqueueStyle('wp-edit-post');
    $this->wp->wpEnqueueStyle('wp-editor');
    $this->wp->wpEnqueueStyle('wp-block-library');
    $this->wp->wpEnqueueStyle('wp-format-library');
    $this->wp->wpEnqueueStyle('wp-interface');

    $this->pageRenderer->displayPage('email_editor.html', []);
  }
}

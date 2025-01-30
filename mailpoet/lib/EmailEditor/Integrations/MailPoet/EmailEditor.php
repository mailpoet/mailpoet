<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\PatternsController;
use MailPoet\EmailEditor\Integrations\MailPoet\Templates\TemplatesController;
use MailPoet\WP\Functions as WPFunctions;

class EmailEditor {
  const MAILPOET_EMAIL_POST_TYPE = 'mailpoet_email';

  private WPFunctions $wp;

  private EmailApiController $emailApiController;

  private EditorPageRenderer $editorPageRenderer;

  private PatternsController $patternsController;

  private Cli $cli;

  private EmailEditorPreviewEmail $emailEditorPreviewEmail;

  private PersonalizationTagManager $personalizationTagManager;

  private TemplatesController $templatesController;

  public function __construct(
    WPFunctions $wp,
    EmailApiController $emailApiController,
    EditorPageRenderer $editorPageRenderer,
    EmailEditorPreviewEmail $emailEditorPreviewEmail,
    PatternsController $patternsController,
    TemplatesController $templatesController,
    Cli $cli,
    PersonalizationTagManager $personalizationTagManager
  ) {
    $this->wp = $wp;
    $this->emailApiController = $emailApiController;
    $this->editorPageRenderer = $editorPageRenderer;
    $this->patternsController = $patternsController;
    $this->templatesController = $templatesController;
    $this->cli = $cli;
    $this->emailEditorPreviewEmail = $emailEditorPreviewEmail;
    $this->personalizationTagManager = $personalizationTagManager;
  }

  public function initialize(): void {
    $this->cli->initialize();
    $this->wp->addFilter('mailpoet_email_editor_post_types', [$this, 'addEmailPostType']);
    $this->wp->addAction('rest_delete_mailpoet_email', [$this->emailApiController, 'trashEmail'], 10, 1);
    $this->wp->addFilter('mailpoet_is_email_editor_page', [$this, 'isEditorPage'], 10, 1);
    $this->wp->addFilter('replace_editor', [$this, 'replaceEditor'], 10, 2);
    $this->wp->addFilter('mailpoet_email_editor_send_preview_email', [$this->emailEditorPreviewEmail, 'sendPreviewEmail'], 10, 1);
    $this->patternsController->registerPatterns();
    $this->templatesController->initialize();
    $this->extendEmailPostApi();
    $this->personalizationTagManager->initialize();
  }

  public function addEmailPostType(array $postTypes): array {
    $postTypes[] = [
      'name' => self::MAILPOET_EMAIL_POST_TYPE,
      'args' => [
        'labels' => [
          'name' => __('Emails', 'mailpoet'),
          'singular_name' => __('Email', 'mailpoet'),
        ],
        'rewrite' => ['slug' => self::MAILPOET_EMAIL_POST_TYPE],
      ],
    ];
    return $postTypes;
  }

  public function isEditorPage(bool $isEditorPage): bool {
    if ($isEditorPage) {
      return $isEditorPage;
    }
    // We need to check early if we are on the email editor page. The check runs early so we can't use current_screen() here.
    if ($this->wp->isAdmin() && isset($_GET['post']) && isset($_GET['action']) && $_GET['action'] === 'edit') {
      $post = $this->wp->getPost((int)$_GET['post']);
      return $post && $post->post_type === self::MAILPOET_EMAIL_POST_TYPE; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    }
    return false;
  }

  public function extendEmailPostApi() {
    $this->wp->registerRestField(self::MAILPOET_EMAIL_POST_TYPE, 'mailpoet_data', [
      'get_callback' => [$this->emailApiController, 'getEmailData'],
      'update_callback' => [$this->emailApiController, 'saveEmailData'],
      'schema' => $this->emailApiController->getEmailDataSchema(),
    ]);
  }

  public function replaceEditor($replace, $post) {
    $currentScreen = get_current_screen();
    if ($post->post_type === self::MAILPOET_EMAIL_POST_TYPE && $currentScreen) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      $this->editorPageRenderer->render();
      return true;
    }
    return $replace;
  }
}

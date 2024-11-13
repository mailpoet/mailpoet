<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use MailPoet\Features\FeaturesController;
use MailPoet\WP\Functions as WPFunctions;

class EmailEditor {
  const MAILPOET_EMAIL_POST_TYPE = 'mailpoet_email';

  private WPFunctions $wp;

  private FeaturesController $featuresController;

  private EmailApiController $emailApiController;

  private EditorPageRenderer $editorPageRenderer;

  private Cli $cli;

  public function __construct(
    WPFunctions $wp,
    FeaturesController $featuresController,
    EmailApiController $emailApiController,
    EditorPageRenderer $editorPageRenderer,
    Cli $cli
  ) {
    $this->wp = $wp;
    $this->featuresController = $featuresController;
    $this->emailApiController = $emailApiController;
    $this->editorPageRenderer = $editorPageRenderer;
    $this->cli = $cli;
  }

  public function initialize(): void {
    if (!$this->featuresController->isSupported(FeaturesController::GUTENBERG_EMAIL_EDITOR)) {
      return;
    }
    $this->cli->initialize();
    $this->wp->addFilter('mailpoet_email_editor_post_types', [$this, 'addEmailPostType']);
    $this->wp->addAction('rest_delete_mailpoet_email', [$this->emailApiController, 'trashEmail'], 10, 1);
    $this->wp->addFilter('mailpoet_is_email_editor_page', [$this, 'isEditorPage'], 10, 1);
    $this->wp->addFilter('replace_editor', [$this, 'replaceEditor'], 10, 2);
    $this->extendEmailPostApi();
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
    // Check on for post editor page
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
    if (use_block_editor_for_post($post) && $post->post_type === self::MAILPOET_EMAIL_POST_TYPE && $currentScreen) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

      $this->editorPageRenderer->render();
      return true;
    }
    return $replace;
  }
}

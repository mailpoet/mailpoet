<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use MailPoet\API\REST\API;
use MailPoet\Config\AccessControl;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\EmailPresetPatterns;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\EmailPresetPostType;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailPresets\RestApi\Api as EmailPresetsApi;
use MailPoet\EmailEditor\Integrations\MailPoet\Endpoints\GenerateSubjectSuggestionsEndpoint;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\PatternsController;
use MailPoet\EmailEditor\Integrations\MailPoet\Templates\TemplatesController;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\WP\Functions as WPFunctions;

class EmailEditor {
  const MAILPOET_EMAIL_POST_TYPE = 'mailpoet_email';
  // Post types that MailPoet's templates, patterns and personalization tags support.
  const EDITOR_POST_TYPES = [self::MAILPOET_EMAIL_POST_TYPE, EmailPresetPostType::NAME];

  private const POST_TYPE_CAPABILITIES = [
    'read' => AccessControl::PERMISSION_MANAGE_EMAILS,
    'edit_posts' => AccessControl::PERMISSION_MANAGE_EMAILS,
    'edit_others_posts' => AccessControl::PERMISSION_MANAGE_EMAILS,
    'edit_published_posts' => AccessControl::PERMISSION_MANAGE_EMAILS,
    'edit_private_posts' => AccessControl::PERMISSION_MANAGE_EMAILS,
    'publish_posts' => AccessControl::PERMISSION_MANAGE_EMAILS,
    'read_private_posts' => AccessControl::PERMISSION_MANAGE_EMAILS,
    'delete_posts' => AccessControl::PERMISSION_MANAGE_EMAILS,
    'delete_others_posts' => AccessControl::PERMISSION_MANAGE_EMAILS,
    'delete_published_posts' => AccessControl::PERMISSION_MANAGE_EMAILS,
    'delete_private_posts' => AccessControl::PERMISSION_MANAGE_EMAILS,
    'create_posts' => AccessControl::PERMISSION_MANAGE_EMAILS,
  ];

  private WPFunctions $wp;

  private EmailApiController $emailApiController;

  private EditorPageRenderer $editorPageRenderer;

  private PatternsController $patternsController;

  private Cli $cli;

  private EmailEditorPreviewEmail $emailEditorPreviewEmail;

  private PersonalizationTagManager $personalizationTagManager;

  private TemplatesController $templatesController;

  private NewslettersRepository $newslettersRepository;

  private EmailPresetPostType $emailPresetPostType;

  private EmailPresetPatterns $emailPresetPatterns;

  private EmailPresetsApi $emailPresetsApi;

  private bool $editorAlreadyRendered = false;

  public function __construct(
    WPFunctions $wp,
    EmailApiController $emailApiController,
    EditorPageRenderer $editorPageRenderer,
    EmailEditorPreviewEmail $emailEditorPreviewEmail,
    PatternsController $patternsController,
    TemplatesController $templatesController,
    Cli $cli,
    PersonalizationTagManager $personalizationTagManager,
    NewslettersRepository $newslettersRepository,
    EmailPresetPostType $emailPresetPostType,
    EmailPresetPatterns $emailPresetPatterns,
    EmailPresetsApi $emailPresetsApi
  ) {
    $this->wp = $wp;
    $this->emailApiController = $emailApiController;
    $this->editorPageRenderer = $editorPageRenderer;
    $this->patternsController = $patternsController;
    $this->templatesController = $templatesController;
    $this->cli = $cli;
    $this->emailEditorPreviewEmail = $emailEditorPreviewEmail;
    $this->personalizationTagManager = $personalizationTagManager;
    $this->newslettersRepository = $newslettersRepository;
    $this->emailPresetPostType = $emailPresetPostType;
    $this->emailPresetPatterns = $emailPresetPatterns;
    $this->emailPresetsApi = $emailPresetsApi;
  }

  public function initialize(): void {
    $this->cli->initialize();
    $this->wp->addFilter('woocommerce_email_editor_post_types', [$this, 'addEmailPostType']);
    $this->wp->addAction('rest_delete_mailpoet_email', [$this->emailApiController, 'trashEmail'], 10, 1);
    $this->wp->addAction('rest_after_insert_mailpoet_email', [$this->emailApiController, 'markNewsletterAsUpdated'], 10, 1);
    $this->wp->addFilter('woocommerce_is_email_editor_page', [$this, 'isEditorPage'], 10, 1);
    $this->wp->addFilter('replace_editor', [$this, 'replaceEditor'], 10, 2);
    $this->wp->addFilter('woocommerce_email_editor_send_preview_email', [$this->emailEditorPreviewEmail, 'sendPreviewEmail'], 10, 1);
    $this->wp->addFilter('woocommerce_email_editor_send_preview_email_personalizer_context', [$this, 'extendPreviewPersonalizerContext'], 10, 1);
    $this->wp->addFilter('rest_pre_insert_mailpoet_email', [$this, 'preserveAutomationEmailStatus'], 10, 2);
    $this->patternsController->registerPatterns();
    $this->templatesController->initialize();
    $this->extendEmailPostApi();
    $this->registerApiRoutes();
    $this->personalizationTagManager->initialize();
    if ($this->emailPresetPostType->isEnabled()) {
      $this->emailPresetPostType->registerMeta();
      $this->emailPresetPatterns->initialize();
      $this->emailPresetsApi->initialize();
    }
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
        'map_meta_cap' => true,
        'capabilities' => self::POST_TYPE_CAPABILITIES,
      ],
    ];
    if ($this->emailPresetPostType->isEnabled()) {
      $postTypes[] = $this->emailPresetPostType->getDefinition(self::POST_TYPE_CAPABILITIES);
    }
    return $postTypes;
  }

  public function isEditorPage(bool $isEditorPage): bool {
    if ($isEditorPage) {
      return $isEditorPage;
    }
    // We need to check early if we are on the email editor page. The check runs early so we can't use current_screen() here.
    if ($this->wp->isAdmin() && isset($_GET['post']) && isset($_GET['action']) && $_GET['action'] === 'edit' && is_numeric($_GET['post'])) {
      $post = $this->wp->getPost((int)$_GET['post']);
      return $this->isEditorPost($post);
    }
    return false;
  }

  private function registerApiRoutes(): void {
    $this->wp->addAction(API::REST_API_INIT_ACTION, function (API $api) {
      $api->registerPostRoute(
        'email/generate-subject-suggestions',
        GenerateSubjectSuggestionsEndpoint::class
      );
    });
  }

  /**
   * @param \WP_Post|mixed $post
   */
  public function isEditorPost($post): bool {
    if (!$post instanceof \WP_Post) {
      return false;
    }
    return $post->post_type === self::MAILPOET_EMAIL_POST_TYPE || $this->emailPresetPostType->isPreset($post); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  public function extendEmailPostApi() {
    $this->wp->registerRestField(self::MAILPOET_EMAIL_POST_TYPE, 'mailpoet_data', [
      'get_callback' => [$this->emailApiController, 'getEmailData'],
      'update_callback' => [$this->emailApiController, 'saveEmailData'],
      'schema' => $this->emailApiController->getEmailDataSchema(),
    ]);
  }

  public function replaceEditor($replace, $post) {
    $currentScreen = $this->wp->getCurrentScreen();
    if ($currentScreen && $this->isEditorPost($post)) {
      // The no-arg WP_Screen::get() re-fires this filter, and some plugins (e.g. Yoast SEO
      // Premium) call it during admin_enqueue_scripts — while render() is still printing
      // the admin header. Rendering again would echo a second editor container into <head>
      // and leave the editor unresponsive, so only report that the editor was replaced.
      if ($this->editorAlreadyRendered) {
        return true;
      }
      $this->editorAlreadyRendered = true;
      $this->editorPageRenderer->render();
      return true;
    }
    return $replace;
  }

  /**
   * Extend preview personalizer context with automation sample data.
   * This is called by WooCommerce Email Editor when rendering preview in browser.
   *
   * @param array<string, mixed> $context
   * @return array<string, mixed>
   */
  public function extendPreviewPersonalizerContext(array $context): array {
    // Get the current post being previewed
    $post = $this->wp->getPost();
    if ($this->emailPresetPostType->isPreset($post)) {
      // A preset has no newsletter, so the current user is the recipient.
      $context['recipient_email'] = $context['recipient_email'] ?? $this->wp->wpGetCurrentUser()->user_email;
      $context['is_preview'] = true;
      $this->personalizationTagManager->extendPersonalizationTagsByAllSubjects();
      return $context;
    }
    if (!$post || $post->post_type !== self::MAILPOET_EMAIL_POST_TYPE) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      return $context;
    }

    $newsletter = $this->newslettersRepository->findOneBy(['wpPost' => $post->ID]);
    if (!$newsletter || (!$newsletter->isAutomation() && !$newsletter->isAutomationTransactional())) {
      return $context;
    }

    // Get automation ID and extend tags
    $automationId = $newsletter->getOptionValue('automationId');
    if (!$automationId) {
      return $context;
    }

    // Extend personalization tags based on automation subjects
    $this->personalizationTagManager->extendPersonalizationTagsByAutomationSubjects((int)$automationId);

    // Context is populated via woocommerce_email_editor_send_preview_email_personalizer_context filter
    // which extensions can hook into to add their sample data
    return $context;
  }

  /**
   * Preserve the 'private' status for automation emails when updating via REST API.
   * This prevents the block editor from changing the status to 'draft' during autosave.
   *
   * @param \stdClass $preparedPost The prepared post data for insertion.
   * @param \WP_REST_Request $request The REST request object.
   * @return \stdClass The modified post data.
   */
  public function preserveAutomationEmailStatus($preparedPost, $request) {
    if (empty($preparedPost->ID)) {
      return $preparedPost;
    }

    $newsletter = $this->newslettersRepository->findOneBy(['wpPost' => $preparedPost->ID]);
    if ($newsletter && ($newsletter->isAutomation() || $newsletter->isAutomationTransactional())) {
      $preparedPost->post_status = 'private'; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    }

    return $preparedPost;
  }
}

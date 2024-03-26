<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Validator\Builder;
use WP_Post;
use WP_Theme_JSON;

/**
 * @phpstan-type EmailPostType array{name: string, args: array, meta: array{key: string, args: array}[]}
 * See register_post_type for details about EmailPostType args.
 */
class EmailEditor {
  public const MAILPOET_EMAIL_META_THEME_TYPE = 'mailpoet_email_theme';

  /** @var EmailApiController */
  private $emailApiController;

  public function __construct(
    EmailApiController $emailApiController
  ) {
    $this->emailApiController = $emailApiController;
  }

  public function initialize(): void {
    do_action('mailpoet_email_editor_initialized');
    add_filter('mailpoet_email_editor_rendering_theme_styles', [$this, 'extendEmailThemeStyles'], 10, 2);
    $this->registerEmailPostTypes();
    $this->registerEmailMetaFields();
    $this->registerEmailPostSendStatus();
    $this->extendEmailPostApi();
  }

  /**
   * Register all custom post types that should be edited via the email editor
   * The post types are added via mailpoet_email_editor_post_types filter.
   */
  private function registerEmailPostTypes(): void {
    foreach ($this->getPostTypes() as $postType) {
      register_post_type(
        $postType['name'],
        array_merge($this->getDefaultEmailPostArgs(), $postType['args'])
      );
    }
  }

  private function registerEmailMetaFields(): void {
    foreach ($this->getPostTypes() as $postType) {
      register_post_meta(
        $postType['name'],
        self::MAILPOET_EMAIL_META_THEME_TYPE,
        [
          'show_in_rest' => [
            'schema' => $this->getEmailThemeDataSchema(),
          ],
          'single' => true,
          'type' => 'object',
          'default' => ['version' => 2], // The version 2 is important to merge themes correctly
        ]
      );
    }
  }

  /**
   * @phpstan-return EmailPostType[]
   */
  private function getPostTypes(): array {
    $postTypes = [];
    return apply_filters('mailpoet_email_editor_post_types', $postTypes);
  }

  private function getDefaultEmailPostArgs(): array {
    return [
      'public' => false,
      'hierarchical' => false,
      'show_ui' => true,
      'show_in_menu' => false,
      'show_in_nav_menus' => false,
      'supports' => ['editor', 'title', 'custom-fields'], // 'custom-fields' is required for loading meta fields via API
      'has_archive' => true,
      'show_in_rest' => true, // Important to enable Gutenberg editor
    ];
  }

  private function registerEmailPostSendStatus(): void {
    register_post_status(NewsletterEntity::STATUS_SENT, [
        'public' => false,
        'exclude_from_search' => true,
        'internal' => true, // for now, we hide it, if we use the status in the listings we may flip this and following values
        'show_in_admin_all_list' => false,
        'show_in_admin_status_list' => false,
      ]);
  }

  public function extendEmailPostApi() {
    $emailPostTypes = array_column($this->getPostTypes(), 'name');
    register_rest_field($emailPostTypes, 'email_data', [
      'get_callback' => [$this->emailApiController, 'getEmailData'],
      'update_callback' => [$this->emailApiController, 'saveEmailData'],
      'schema' => $this->emailApiController->getEmailDataSchema(),
    ]);
  }

  public function getEmailThemeDataSchema(): array {
    $typographyProps = Builder::object([
      'fontFamily' => Builder::string()->nullable(),
      'fontSize' => Builder::string()->nullable(),
      'fontStyle' => Builder::string()->nullable(),
      'fontWeight' => Builder::string()->nullable(),
      'letterSpacing' => Builder::string()->nullable(),
      'lineHeight' => Builder::string()->nullable(),
    ])->nullable();
    return Builder::object([
      'version' => Builder::integer(),
      'styles' => Builder::object([
        'spacing' => Builder::object([
          'padding' => Builder::object([
            'top' => Builder::string(),
            'right' => Builder::string(),
            'bottom' => Builder::string(),
            'left' => Builder::string(),
          ])->nullable(),
          'blockGap' => Builder::string()->nullable(),
        ])->nullable(),
        'typography' => $typographyProps,
        'elements' => Builder::object([
          'heading' => Builder::object([
            'typography' => $typographyProps,
          ])->nullable(),
          'button' => Builder::object([
            'typography' => $typographyProps,
          ])->nullable(),
          'link' => Builder::object([
            'typography' => $typographyProps,
          ])->nullable(),
          'h1' => Builder::object([
            'typography' => $typographyProps,
          ])->nullable(),
          'h2' => Builder::object([
            'typography' => $typographyProps,
          ])->nullable(),
          'h3' => Builder::object([
            'typography' => $typographyProps,
          ])->nullable(),
          'h4' => Builder::object([
            'typography' => $typographyProps,
          ])->nullable(),
          'h5' => Builder::object([
            'typography' => $typographyProps,
          ])->nullable(),
          'h6' => Builder::object([
            'typography' => $typographyProps,
          ])->nullable(),
        ])->nullable(),
      ])->nullable(),
    ])->toArray();
  }

  public function extendEmailThemeStyles(WP_Theme_JSON $theme, WP_Post $post): WP_Theme_JSON {
    $emailTheme = get_post_meta($post->ID, EmailEditor::MAILPOET_EMAIL_META_THEME_TYPE, true);
    if ($emailTheme && is_array($emailTheme)) {
      $theme->merge(new WP_Theme_JSON($emailTheme));
    }
    return $theme;
  }
}

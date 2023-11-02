<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine;

/**
 * @phpstan-type EmailPostType array{name: string, args: array}
 * See register_post_type for details about EmailPostType args.
 */
class EmailEditor {
  /** @var EmailApiController */
  private $emailApiController;

  public function __construct(
    EmailApiController $emailApiController
  ) {
    $this->emailApiController = $emailApiController;
  }

  public function initialize(): void {
    do_action('mailpoet_email_editor_initialized');
    $this->registerEmailPostTypes();
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
      'supports' => ['editor', 'title'],
      'has_archive' => true,
      'show_in_rest' => true, // Important to enable Gutenberg editor
    ];
  }

  public function extendEmailPostApi() {
    $emailPostTypes = array_column($this->getPostTypes(), 'name');
    register_rest_field($emailPostTypes, 'email_data', [
      'get_callback' => [$this->emailApiController, 'getEmailData'],
      'update_callback' => [$this->emailApiController, 'saveEmailData'],
      'schema' => $this->emailApiController->getEmailDataSchema(),
    ]);
  }
}

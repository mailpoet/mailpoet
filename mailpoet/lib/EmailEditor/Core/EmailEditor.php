<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Core;

/**
 * @phpstan-type EmailPostType array{name: string, args: array}
 * See register_post_type for details about EmailPostType args.
 */
class EmailEditor {
  public function initialize(): void {
    $this->registerEmailPostTypes();
  }

  private function registerEmailPostTypes() {
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
      'supports' => ['editor'],
      'has_archive' => true,
      'show_in_rest' => true, // Important to enable Gutenberg editor
    ];
  }
}

<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Core;

/**
 * @phpstan-type EmailPostType array{name: string, args: array}
 * See register_post_type for details about EmailPostType args.
 */
class EmailEditor {
  private const ALLOWED_BLOCK_TYPES = ['core/paragraph', 'core/heading', 'core/column', 'core/columns'];

  public function initialize(): void {
    $this->registerEmailPostTypes();
    add_filter('allowed_block_types_all', [$this, 'setAllowedBlocksInEmails'], 100, 2);
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

  /**
   * @param string[]|bool $allowedBlockTypes
   * @param \WP_Block_Editor_Context $blockEditorContext
   * @return array|bool
   */
  public function setAllowedBlocksInEmails($allowedBlockTypes, \WP_Block_Editor_Context $blockEditorContext) {
    $emailPostTypes = array_column($this->getPostTypes(), 'name');
    if (!$blockEditorContext->post || !in_array($blockEditorContext->post->post_type, $emailPostTypes, true)) {
      return $allowedBlockTypes;
    }
    return self::ALLOWED_BLOCK_TYPES;
  }
}

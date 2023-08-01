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
    add_filter('enqueue_block_editor_assets', [$this, 'cleanupBlockEditorAssets'], ~PHP_INT_MAX);
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

  /**
   * This method removes all callbacks registered for enqueue_block_editor_assets action
   * except ones allowed via mailpoet_email_editor_allowed_editor_assets_actions filter.
   *
   * This is to prevent 3rd party plugins which don't check post type from breaking the email editor.
   */
  public function cleanupBlockEditorAssets(): void {
    $emailPostTypes = array_column($this->getPostTypes(), 'name');
    if (!in_array(get_post_type(), $emailPostTypes, true)) {
      return;
    }

    $allowedActions = apply_filters(
      'mailpoet_email_editor_allowed_editor_assets_actions',
      [
        __CLASS__ . '::cleanupBlockEditorAssets',
        'wp_enqueue_editor_format_library_assets',
        'wp_enqueue_editor_block_directory_assets',
        'wp_enqueue_registered_block_scripts_and_styles',
        'enqueue_editor_block_styles_assets',
        'wp_enqueue_global_styles_css_custom_properties',
      ]
    );

    $assetsActions = $GLOBALS['wp_filter']['enqueue_block_editor_assets']->callbacks;
    foreach ($assetsActions as $priority => $actions) {
      foreach ($actions as $action) {
        $actionName = is_array($action['function']) ? get_class($action['function'][0]) . '::' . $action['function'][1] : $action['function'];
        if (in_array($actionName, $allowedActions, true)) {
          continue;
        }
        remove_action('enqueue_block_editor_assets', $action['function'], $priority);
      }
    }
  }
}

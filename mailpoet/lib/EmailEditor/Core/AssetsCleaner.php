<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Core;

/**
 * The email editor is built on top of the WP built-in post editor.
 * Some plugins are hooking into the editor and adding their own assets without checking the post type.
 * This class removes all assets which are not needed for the email editor to prevent unwanted extensions.
 *
 * If you want to add assets to the email editor, use the mailpoet_email_editor_allowed_editor_assets_actions filter.
 *
 * @todo We may need to cleanup more hooks (e.g. enqueue_block_assets) in the future.
 */
class AssetsCleaner {
  private const ALLOWED_ACTIONS = [
    __CLASS__ . '::cleanupBlockEditorAssets',
    'wp_enqueue_editor_format_library_assets',
    'wp_enqueue_editor_block_directory_assets',
    'wp_enqueue_registered_block_scripts_and_styles',
    'enqueue_editor_block_styles_assets',
    'wp_enqueue_global_styles_css_custom_properties',
  ];

  /**
   * This method removes all callbacks registered for enqueue_block_editor_assets action
   * except ones allowed via mailpoet_email_editor_allowed_editor_assets_actions filter.
   *
   * This is to prevent 3rd party plugins which don't check post type from breaking the email editor.
   */
  public function cleanupBlockEditorAssets(): void {
    $allowedActions = $this->getAllowedActions();

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

  private function getAllowedActions(): array {
    return apply_filters(
      'mailpoet_email_editor_allowed_editor_assets_actions',
      self::ALLOWED_ACTIONS
    );
  }
}

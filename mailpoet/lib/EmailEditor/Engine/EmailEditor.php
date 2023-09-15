<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine;

/**
 * @phpstan-type EmailPostType array{name: string, args: array}
 * See register_post_type for details about EmailPostType args.
 */
class EmailEditor {
  private const ALLOWED_BLOCK_TYPES = ['core/paragraph', 'core/heading', 'core/column', 'core/columns'];

  /** @var AssetsCleaner */
  private $assetsCleaner;

  /** @var StylesController */
  private $stylesController;

  /**
   * @param AssetsCleaner $assetsCleaner
   */
  public function __construct(
    AssetsCleaner $assetsCleaner,
    StylesController $stylesController
  ) {
    $this->assetsCleaner = $assetsCleaner;
    $this->stylesController = $stylesController;
  }

  public function initialize(): void {
    add_filter('allowed_block_types_all', [$this, 'setAllowedBlocksInEmails'], 100, 2);
    add_filter('enqueue_block_editor_assets', [$this, 'cleanupBlockEditorAssets'], ~PHP_INT_MAX);
    add_filter('block_editor_settings_all', [$this, 'updateBlockEditorSettings'], 100, 2);
    do_action('mailpoet_email_editor_initialized');
    $this->registerEmailPostTypes();
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

  public function cleanupBlockEditorAssets() {
    $emailPostTypes = array_column($this->getPostTypes(), 'name');
    if (!in_array(get_post_type(), $emailPostTypes, true)) {
      return;
    }
    $this->assetsCleaner->cleanupBlockEditorAssets();
  }

  public function updateBlockEditorSettings(array $settings, \WP_Block_Editor_Context $blockEditorContext): array {
    $emailPostTypes = array_column($this->getPostTypes(), 'name');
    if (!$blockEditorContext->post || !in_array($blockEditorContext->post->post_type, $emailPostTypes, true)) {
      return $settings;
    }
    $settings['enableCustomUnits'] = ['px', '%']; // Allow only units we can support in email renderer
    $settings['__experimentalAdditionalBlockPatterns'] = [];
    $settings['__experimentalAdditionalBlockPatternCategories'] = [];

    // Reset editor styles f
    $settings['defaultEditorStyles'] = [[ 'css' => $this->stylesController->getEmailContentStyles() ]];

    return apply_filters('mailpoet_email_editor_settings_all', $settings);
  }
}

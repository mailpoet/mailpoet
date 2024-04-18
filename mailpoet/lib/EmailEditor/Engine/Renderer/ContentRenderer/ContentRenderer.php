<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\ContentRenderer;

use MailPoet\EmailEditor\Engine\SettingsController;
use MailPoet\EmailEditor\Engine\ThemeController;
use MailPoetVendor\Pelago\Emogrifier\CssInliner;
use WP_Block_Template;
use WP_Post;

class ContentRenderer {
  private BlocksRegistry $blocksRegistry;
  private ProcessManager $processManager;
  private SettingsController $settingsController;
  private ThemeController $themeController;
  private $layoutSettings;
  private $themeStyles;

  const CONTENT_STYLES_FILE = 'content.css';

  public function __construct(
    ProcessManager $preprocessManager,
    BlocksRegistry $blocksRegistry,
    SettingsController $settingsController,
    ThemeController $themeController
  ) {
    $this->processManager = $preprocessManager;
    $this->blocksRegistry = $blocksRegistry;
    $this->settingsController = $settingsController;

    $this->themeController = $themeController;
  }

  private function initialize() {
    $this->layoutSettings = $this->settingsController->getLayout();
    $this->themeStyles = $this->settingsController->getEmailStyles();
    add_filter('block_parser_class', [$this, 'blockParser']);
    add_filter('mailpoet_blocks_renderer_parsed_blocks', [$this, 'preprocessParsedBlocks']);
    do_action('mailpoet_blocks_renderer_initialized', $this->blocksRegistry);
  }

  private function setTemplateGlobals(WP_Post $post, WP_Block_Template $template) {
    global $_wp_current_template_content, $_wp_current_template_id;
    $_wp_current_template_id = $template->id;
    $_wp_current_template_content = $template->content;
    $GLOBALS['post'] = $post;
  }

  /**
  * As we use default WordPress filters, we need to remove them after email rendering
  * so that we don't interfere with possible post rendering that might happen later.
  */
  private function reset() {
    $this->blocksRegistry->removeAllBlockRendererFilters();
    remove_filter('block_parser_class', [$this, 'blockParser']);
    remove_filter('mailpoet_blocks_renderer_parsed_blocks', [$this, 'preprocessParsedBlocks']);
  }

  public function blockParser() {
    return 'MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\BlocksParser';
  }

  public function preprocessParsedBlocks(array $parsedBlocks): array {
    return $this->processManager->preprocess($parsedBlocks, $this->layoutSettings, $this->themeStyles);
  }

  public function render(WP_Post $post, WP_Block_Template $template): string {
    $this->initialize();
    $this->setTemplateGlobals($post, $template);

    $renderedHtml = $this->processManager->postprocess(get_the_block_template_html());

    $this->reset();

    return $this->inlineStyles($renderedHtml, $post);
  }

  /**
   * @param string $html
   * @return string
   */
  private function inlineStyles($html, WP_Post $post) {
    $styles = (string)file_get_contents(dirname(__FILE__) . '/' . self::CONTENT_STYLES_FILE);
    $styles .= $this->themeController->getStylesheetForRendering($post);
    // Get styles from block-supports stylesheet. This includes rules such as layout (contentWidth) that some blocks use.
    // @see https://github.com/WordPress/WordPress/blob/3c5da9c74344aaf5bf8097f2e2c6a1a781600e03/wp-includes/script-loader.php#L3134
    // @internal :where is not supported by emogrifier, so we need to replace it with *.
    $styles .= str_replace(
      ':where(:not(.alignleft):not(.alignright):not(.alignfull))',
      '*:not(.alignleft):not(.alignright):not(.alignfull)',
      \wp_style_engine_get_stylesheet_from_context('block-supports', [])
    );
    $styles = '<style>' . (string)apply_filters('mailpoet_email_content_renderer_styles', $styles, $post) . '</style>';

    return CssInliner::fromHtml($styles . $html)->inlineCss()->render();
  }
}

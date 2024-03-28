<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\ContentRenderer;

use MailPoet\EmailEditor\Engine\SettingsController;
use WP_Block_Template;
use WP_Post;

class ContentRenderer {
  private BlocksRegistry $blocksRegistry;
  private ProcessManager $processManager;
  private SettingsController $settingsController;
  private $layoutSettings;
  private $themeStyles;

  public function __construct(
    ProcessManager $preprocessManager,
    BlocksRegistry $blocksRegistry,
    SettingsController $settingsController
  ) {
    $this->processManager = $preprocessManager;
    $this->blocksRegistry = $blocksRegistry;
    $this->settingsController = $settingsController;
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

    return $renderedHtml;
  }
}

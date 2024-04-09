<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\ContentRenderer;

use MailPoet\EmailEditor\Engine\SettingsController;
use MailPoet\EmailEditor\Engine\ThemeController;
use MailPoet\Util\pQuery\DomNode;
use MailPoetVendor\CSS;
use WP_Block_Template;
use WP_Post;

class ContentRenderer {
  private CSS $cssInliner;
  private BlocksRegistry $blocksRegistry;
  private ProcessManager $processManager;
  private SettingsController $settingsController;
  private ThemeController $themeController;
  private $layoutSettings;
  private $themeStyles;

  const CONTENT_STYLES_FILE = 'content.css';

  public function __construct(
    CSS $cssInliner,
    ProcessManager $preprocessManager,
    BlocksRegistry $blocksRegistry,
    SettingsController $settingsController,
    ThemeController $themeController
  ) {
    $this->processManager = $preprocessManager;
    $this->blocksRegistry = $blocksRegistry;
    $this->settingsController = $settingsController;
    $this->cssInliner = $cssInliner;
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
    $styles .= $this->themeController->getStylesheetForRendering();
    $styles = '<style>' . (string)apply_filters('mailpoet_email_content_renderer_styles', $styles, $post) . '</style>';

    return $this->postProcessTemplate(
      $this->cssInliner->inlineCSS($styles . $html)
    );
  }

  /**
   * @param DomNode $templateDom
   * @return string
   */
  private function postProcessTemplate(DomNode $templateDom) {
    // because tburry/pquery contains a bug and replaces the opening non mso condition incorrectly we have to replace the opening tag with correct value
    $template = $templateDom->__toString();
    $template = str_replace('<!--[if !mso]><![endif]-->', '<!--[if !mso]><!-- -->', $template);
    return $template;
  }
}

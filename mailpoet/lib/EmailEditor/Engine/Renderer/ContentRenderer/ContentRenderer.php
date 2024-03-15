<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\ContentRenderer;

use MailPoet\EmailEditor\Engine\SettingsController;
use MailPoet\EmailEditor\Engine\ThemeController;
use MailPoet\Util\pQuery\DomNode;

class ContentRenderer {
  private \MailPoetVendor\CSS $cssInliner;

  private BlocksRegistry $blocksRegistry;

  private ProcessManager $processManager;

  private SettingsController $settingsController;

  private ThemeController $themeController;

  const CONTENT_STYLES_FILE = 'content.css';

  /**
   * @param \MailPoetVendor\CSS $cssInliner
   */
  public function __construct(
    \MailPoetVendor\CSS $cssInliner,
    ProcessManager $preprocessManager,
    BlocksRegistry $blocksRegistry,
    SettingsController $settingsController,
    ThemeController $themeController
  ) {
    $this->cssInliner = $cssInliner;
    $this->processManager = $preprocessManager;
    $this->blocksRegistry = $blocksRegistry;
    $this->settingsController = $settingsController;
    $this->themeController = $themeController;
  }

  public function render(\WP_Post $post): string {
    $parser = new \WP_Block_Parser();
    $parsedBlocks = $parser->parse($post->post_content); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

    $layoutStyles = $this->settingsController->getEmailStyles()['layout'];
    $parsedBlocks = $this->processManager->preprocess($parsedBlocks, $layoutStyles);
    $renderedBody = $this->renderBlocks($parsedBlocks);

    $styles = (string)file_get_contents(dirname(__FILE__) . '/' . self::CONTENT_STYLES_FILE);
    $styles .= $this->themeController->getStylesheetForRendering();
    $styles = apply_filters('mailpoet_email_content_renderer_styles', $styles, $post);

    $renderedBodyDom = $this->inlineCSSStyles("<style>$styles</style>" . $renderedBody);
    $renderedBody = $this->postProcessTemplate($renderedBodyDom);
    $renderedBody = $this->processManager->postprocess($renderedBody);
    return $renderedBody;
  }

  public function renderBlocks(array $parsedBlocks): string {
    do_action('mailpoet_blocks_renderer_initialized', $this->blocksRegistry);

    $content = '';
    foreach ($parsedBlocks as $parsedBlock) {
      $content .= render_block($parsedBlock);
    }

    /**
     *  As we use default WordPress filters, we need to remove them after email rendering
     *  so that we don't interfere with possible post rendering that might happen later.
     */
    $this->blocksRegistry->removeAllBlockRendererFilters();

    return $content;
  }

  private function inlineCSSStyles(string $template): DomNode {
    return $this->cssInliner->inlineCSS($template);
  }

  private function postProcessTemplate(DomNode $templateDom): string {
    // because tburry/pquery contains a bug and replaces the opening non mso condition incorrectly we have to replace the opening tag with correct value
    $template = $templateDom->__toString();
    $template = str_replace('<!--[if !mso]><![endif]-->', '<!--[if !mso]><!-- -->', $template);
    return $template;
  }
}

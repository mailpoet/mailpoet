<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core;

use MailPoet\EmailEditor\Engine\Renderer\BlocksRegistry;
use MailPoet\EmailEditor\Engine\Renderer\Layout\FlexLayoutRenderer;

class Initializer {
  public function initialize(): void {
    add_action('mailpoet_blocks_renderer_initialized', [$this, 'registerCoreBlocksRenderers'], 10, 1);
    add_filter('mailpoet_email_editor_theme_json', [$this, 'adjustThemeJson'], 10, 1);
    add_filter('mailpoet_email_editor_editor_styles', [$this, 'addEditorStyles'], 10, 1);
    add_filter('mailpoet_email_renderer_styles', [$this, 'addRendererStyles'], 10, 1);
  }

  /**
   * Register core blocks email renderers when the blocks renderer is initialized.
   */
  public function registerCoreBlocksRenderers(BlocksRegistry $blocksRegistry): void {
    $blocksRegistry->addBlockRenderer('core/paragraph', new Renderer\Blocks\Paragraph());
    $blocksRegistry->addBlockRenderer('core/heading', new Renderer\Blocks\Heading());
    $blocksRegistry->addBlockRenderer('core/column', new Renderer\Blocks\Column());
    $blocksRegistry->addBlockRenderer('core/columns', new Renderer\Blocks\Columns());
    $blocksRegistry->addBlockRenderer('core/list', new Renderer\Blocks\ListBlock());
    $blocksRegistry->addBlockRenderer('core/image', new Renderer\Blocks\Image());
    $blocksRegistry->addBlockRenderer('core/buttons', new Renderer\Blocks\Buttons(new FlexLayoutRenderer()));
    $blocksRegistry->addBlockRenderer('core/button', new Renderer\Blocks\Button());
  }

  /**
   * Adjusts the editor's theme to add blocks specific settings for core blocks.
   */
  public function adjustThemeJson(\WP_Theme_JSON $editorThemeJson): \WP_Theme_JSON {
    $themeJson = (string)file_get_contents(dirname(__FILE__) . '/theme.json');
    $themeJson = json_decode($themeJson, true);
    /** @var array $themeJson */
    $editorThemeJson->merge(new \WP_Theme_JSON($themeJson, 'default'));
    return $editorThemeJson;
  }

  public function addEditorStyles(array $styles) {
    $declaration = (string)file_get_contents(dirname(__FILE__) . '/styles.css');
    $styles[] = ['css' => $declaration];
    return $styles;
  }

  public function addRendererStyles(string $styles) {
    $declaration = (string)file_get_contents(dirname(__FILE__) . '/styles.css');
    $styles .= $declaration;
    return $styles;
  }
}

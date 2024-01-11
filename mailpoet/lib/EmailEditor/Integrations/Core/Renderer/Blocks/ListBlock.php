<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;

// We have to avoid using keyword `List`
class ListBlock implements BlockRenderer {
  public function render(string $blockContent, array $parsedBlock, SettingsController $settingsController): string {
    $html = new \WP_HTML_Tag_Processor($blockContent);
    $tagName = ($parsedBlock['attrs']['ordered'] ?? false) ? 'ol' : 'ul';
    if ($html->next_tag(['tag_name' => $tagName])) {
      $styles = $html->get_attribute('style') ?? '';
      $styles = $settingsController->parseStylesToArray($styles);
      $styles = array_merge($styles, $parsedBlock['email_attrs'] ?? []);

      // List block does not need width specification and it can cause issues for nested lists
      unset($styles['width'] );

      // Use font-size and font-family from Settings when those properties are not set
      $contentStyles = $settingsController->getEmailContentStyles();
      if (!isset($styles['font-size'])) {
        $styles['font-size'] = $contentStyles['typography']['fontSize'];
      }

      $html->set_attribute('style', $settingsController->convertStylesToString($styles));
      $blockContent = $html->get_updated_html();
    }

    // \WP_HTML_Tag_Processor escapes the content, so we have to replace it back
    return str_replace('&#039;', "'", $blockContent);
  }
}

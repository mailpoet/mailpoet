<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;

// We have to avoid using keyword `List`
class ListBlock implements BlockRenderer {
  public function render($blockContent, array $parsedBlock, SettingsController $settingsController): string {
    $contentStyles = $settingsController->getEmailContentStyles();
    return str_replace('{list_content}', $blockContent, $this->getBlockWrapper($parsedBlock, $contentStyles));
  }

  private function getBlockWrapper(array $parsedBlock, array $contentStyles): string {
    $styles = [];
    foreach ($parsedBlock['email_attrs'] ?? [] as $property => $value) {
      $styles[$property] = $value;
    }

    if (!isset($styles['font-size'])) {
      $styles['font-size'] = $contentStyles['typography']['fontSize'];
    }
    if (!isset($styles['font-family'])) {
      $styles['font-family'] = $contentStyles['typography']['fontFamily'];
    }

    return '
      <table
        border="0"
        cellpadding="0"
        cellspacing="0"
        role="presentation"
        style="' . $this->convertStylesToString($styles) . '"
      >
        <tr>
          <td>
            {list_content}
          </td>
        </tr>
      </table>
    ';
  }

  private function convertStylesToString(array $styles): string {
    $cssString = '';
    foreach ($styles as $property => $value) {
      $cssString .= $property . ':' . $value . '; ';
    }
    return trim($cssString); // Remove trailing space and return the formatted string
  }
}

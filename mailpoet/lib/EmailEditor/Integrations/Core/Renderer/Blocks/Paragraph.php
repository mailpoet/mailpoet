<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;

class Paragraph implements BlockRenderer {
  public function render($blockContent, array $parsedBlock, SettingsController $settingsController): string {
    return str_replace('{paragraph_content}', $blockContent, $this->getBlockWrapper($parsedBlock, $settingsController));
  }

  /**
   * Based on MJML <mj-text>
   */
  private function getBlockWrapper(array $parsedBlock, SettingsController $settingsController): string {
    $contentStyles = $settingsController->getEmailContentStyles();

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
        role="presentation"
        border="0"
        cellpadding="0"
        cellspacing="0"
        style="' . $settingsController->convertStylesToString($styles) . '"
      >
        <tr>
          <td>
            {paragraph_content}
          </td>
        </tr>
      </table>
    ';
  }
}

<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;

// We have to avoid using keyword `List`
class ListBlock implements BlockRenderer {
  public function render(string $blockContent, array $parsedBlock, SettingsController $settingsController): string {
    return str_replace('{list_content}', $blockContent, $this->getBlockWrapper($parsedBlock, $settingsController));
  }

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
        border="0"
        cellpadding="0"
        cellspacing="0"
        role="presentation"
        style="' . $settingsController->convertStylesToString($styles) . '"
      >
        <tr>
          <td>
            {list_content}
          </td>
        </tr>
      </table>
    ';
  }
}

<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;

class Image implements BlockRenderer {
  public function render($blockContent, array $parsedBlock, SettingsController $settingsController): string {
    // Replacing HTML tags figure and figcaption because they are not supported by all email clients
    $blockContent = str_replace(
      ['<figure', '</figure>', '<figcaption', '</figcaption>'],
      ['<div', '</div>', '<div', '</div>'],
      $blockContent
    );

    $blockContent = $this->applyRoundedStyle($blockContent, $parsedBlock);
    $blockContent = $this->addImageDimensions($blockContent, $parsedBlock, $settingsController);
    $blockContent = $this->addWidthToWrapper($blockContent, $parsedBlock, $settingsController);
    $blockContent = $this->addCaptionFontSize($blockContent, $settingsController);
    bdump($parsedBlock);
    bdump($blockContent);
    return str_replace('{image_content}', $blockContent, $this->getBlockWrapper($parsedBlock, $settingsController));
  }

  private function applyRoundedStyle($blockContent, array $parsedBlock) {
    // Because the isn't an attribute for definition of rounded style, we have to check the class name
    if (isset($parsedBlock['attrs']['className']) && strpos($parsedBlock['attrs']['className'], 'is-style-rounded') !== false) {
      // If the image should be in a circle, we need to set the border-radius to 100%
      // This style cannot be applied on the wrapper, and we need to set it directly on the image
      $blockContent = $this->addStyleToElement($blockContent, ['tag_name' => 'img'], 'border-radius: 100%;');
    }

    return $blockContent;
  }

  /**
   * Settings width and height attributes for images is important for MS Outlook.
   */
  private function addImageDimensions($blockContent, array $parsedBlock, SettingsController $settingsController) {
    $html = new \WP_HTML_Tag_Processor($blockContent);
    if ($html->next_tag(['tag_name' => 'img'])) {
      // Getting height from styles and if it's set, we set the height attribute
      $styles = $html->get_attribute('style');
      $styles = $settingsController->parseStylesToArray($styles);
      $height = $styles['height'] ?? null;
      if ($height && is_numeric($settingsController->parseNumberFromStringWithPixels($height))) {
        $html->set_attribute('height', $settingsController->parseNumberFromStringWithPixels($height));
      }

      $html->set_attribute('width', $settingsController->parseNumberFromStringWithPixels($parsedBlock['email_attrs']['width']));
      $blockContent = $html->get_updated_html();
    }

    return $blockContent;
  }

  /**
   * We need to reset font-size to avoid unexpected white spaces and set the width of the wrapper
   * for having caption text under the image.
   */
  private function addWidthToWrapper($blockContent, array $parsedBlock, SettingsController $settingsController) {
    $styles = [
      'width' => $parsedBlock['email_attrs']['width'] ?? $parsedBlock['attrs']['width'] ?? '100%',
      'font-size' => '0px',
    ];
    return $this->addStyleToElement($blockContent, ['tag_name' => 'div'], $settingsController->convertStylesToString($styles));
  }

  /**
   * This method configure the font size of the caption because it's set to 0 for the parent element to avoid unexpected white spaces
   */
  private function addCaptionFontSize($blockContent, $settingsController) {
    $contentStyles = $settingsController->getEmailContentStyles();

    if (isset($contentStyles['typography']['fontSize'])) {
      $styles = [
        'font-size' => $contentStyles['typography']['fontSize'],
        'text-align' => 'center',
      ];
      $blockContent = $this->addStyleToElement($blockContent, ['tag_name' => 'div', 'class_name' => 'wp-element-caption'], $settingsController->convertStylesToString($styles));
    }

    return $blockContent;
  }

  /**
   * Based on MJML <mj-image>
   */
  private function getBlockWrapper(array $parsedBlock, SettingsController $settingsController): string {
    $contentStyles = $settingsController->getEmailContentStyles();

    $styles = [
      'border-collapse' => 'collapse',
      'border-spacing' => '0px',
    ];
    $styles = array_merge($styles, $parsedBlock['email_attrs'] ?? []);

    if (!isset($styles['font-size'])) {
      $styles['font-size'] = $contentStyles['typography']['fontSize'];
    }
    if (!isset($styles['font-family'])) {
      $styles['font-family'] = $contentStyles['typography']['fontFamily'];
    }

    $styles['width'] = '100% !important'; // Using important is necessary for Gmail app on mobile devices
    $align = $parsedBlock['attrs']['align'] ?? 'left';

    return '
      <table
        role="presentation"
        border="0"
        cellpadding="0"
        cellspacing="0"
        style="' . $settingsController->convertStylesToString($styles) . '"
      >
        <tr>
          <td align="' . $align . '">
            {image_content}
          </td>
        </tr>
      </table>
    ';
  }

  /**
   * @param array{tag_name: string, class_name?: string} $tag
   * @param string $style
   */
  private function addStyleToElement($blockContent, array $tag, string $style) {
    $html = new \WP_HTML_Tag_Processor($blockContent);
    if ($html->next_tag($tag)) {
      $elementStyle = $html->get_attribute('style');
      $elementStyle = !empty($elementStyle) ? (rtrim($elementStyle, ';') . ';') : ''; // Adding semicolon if it's missing
      $elementStyle .= $style;
      $html->set_attribute('style', $elementStyle);
      $blockContent = $html->get_updated_html();
    }

    return $blockContent;
  }
}

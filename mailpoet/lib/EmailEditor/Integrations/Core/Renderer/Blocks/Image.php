<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;

class Image implements BlockRenderer {
  public function render($blockContent, array $parsedBlock, SettingsController $settingsController): string {
    $parsedHtml = $this->parseBlockContent($blockContent);

    if (!$parsedHtml) {
      return '';
    }

    $imageUrl = $parsedHtml['imageUrl'];
    $image = $parsedHtml['image'];
    $caption = $parsedHtml['caption'];

    $parsedBlock = $this->addImageSizeWhenMissing($parsedBlock, $imageUrl);
    $image = $this->applyRoundedStyle($image, $parsedBlock);
    $image = $this->addImageDimensions($image, $parsedBlock, $settingsController);

    return str_replace(
      ['{image_content}', '{caption_content}'],
      [$image, $caption],
      $this->getBlockWrapper($parsedBlock, $settingsController)
    );
  }

  private function applyRoundedStyle(string $blockContent, array $parsedBlock): string {
    // Because the isn't an attribute for definition of rounded style, we have to check the class name
    if (isset($parsedBlock['attrs']['className']) && strpos($parsedBlock['attrs']['className'], 'is-style-rounded') !== false) {
      // If the image should be in a circle, we need to set the border-radius to 9999px to make it the same as is in the editor
      // This style cannot be applied on the wrapper, and we need to set it directly on the image
      $blockContent = $this->addStyleToElement($blockContent, ['tag_name' => 'img'], 'border-radius: 9999px;');
    }

    return $blockContent;
  }

  /**
   * When the width is not set, it's important to get it for the image to be displayed correctly
   */
  private function addImageSizeWhenMissing(array $parsedBlock, string $imageUrl): array {
    if (!isset($parsedBlock['attrs']['width'])) {
      $maxWidth = $parsedBlock['email_attrs']['width'] ?? '660px';
      $imageSize = wp_getimagesize($imageUrl);
      $imageSize = $imageSize ? "{$imageSize[0]}px" : $maxWidth;
      $parsedBlock['attrs']['width'] = ($imageSize > $maxWidth) ? $maxWidth : $imageSize;
    }
    return $parsedBlock;
  }

  /**
   * Settings width and height attributes for images is important for MS Outlook.
   */
  private function addImageDimensions($blockContent, array $parsedBlock, SettingsController $settingsController): string {
    $html = new \WP_HTML_Tag_Processor($blockContent);
    if ($html->next_tag(['tag_name' => 'img'])) {
      // Getting height from styles and if it's set, we set the height attribute
      $styles = $html->get_attribute('style') ?? '';
      $styles = $settingsController->parseStylesToArray($styles);
      $height = $styles['height'] ?? null;
      if ($height && is_numeric($settingsController->parseNumberFromStringWithPixels($height))) {
        $html->set_attribute('height', $settingsController->parseNumberFromStringWithPixels($height));
      }

      if (isset($parsedBlock['attrs']['width'])) {
        $html->set_attribute('width', $settingsController->parseNumberFromStringWithPixels($parsedBlock['attrs']['width']));
      }
      $blockContent = $html->get_updated_html();
    }

    return $blockContent;
  }

  /**
   * This method configure the font size of the caption because it's set to 0 for the parent element to avoid unexpected white spaces
   */
  private function getCaptionStyles(SettingsController $settingsController, array $parsedBlock): string {
    $contentStyles = $settingsController->getEmailContentStyles();

    // If the alignment is set, we need to center the caption
    $styles = [
      'text-align' => isset($parsedBlock['attrs']['align']) ? 'center' : 'left',
    ];

    if (isset($contentStyles['typography']['fontSize'])) {
      $styles['font-size'] = $contentStyles['typography']['fontSize'];
    }

    return $settingsController->convertStylesToString($styles);
  }

  /**
   * Based on MJML <mj-image> but because MJML doesn't support captions, our solution is a bit different
   */
  private function getBlockWrapper(array $parsedBlock, SettingsController $settingsController): string {
    $styles = [
      'border-collapse' => 'collapse',
      'border-spacing' => '0px',
      'font-size' => '0px',
      'vertical-align' => 'top',
      'width' => '100%',
    ];

    // When the image is not aligned, the wrapper is set to 100% width due to caption that can be longer than the image
    $wrapperWidth = isset($parsedBlock['attrs']['align']) ? ($parsedBlock['attrs']['width'] ?? '100%') : '100%';
    $wrapperStyles = $styles;
    $wrapperStyles['width'] = $wrapperWidth;

    $captionStyles = $this->getCaptionStyles($settingsController, $parsedBlock);

    $styles['width'] = '100%';
    $align = $parsedBlock['attrs']['align'] ?? 'left';

    return '
      <table
        role="presentation"
        border="0"
        cellpadding="0"
        cellspacing="0"
        style="' . $settingsController->convertStylesToString($styles) . '"
        width="100%"
      >
        <tr>
          <td align="' . $align . '">
            <table
              role="presentation"
              border="0"
              cellpadding="0"
              cellspacing="0"
              style="' . $settingsController->convertStylesToString($wrapperStyles) . '"
              width="' . $wrapperWidth . '"
            >
              <tr>
                <td>{image_content}</td>
              </tr>
              <tr>
                <td style="' . $captionStyles . '">{caption_content}</td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    ';
  }

  /**
   * @param array{tag_name: string, class_name?: string} $tag
   * @param string $style
   */
  private function addStyleToElement($blockContent, array $tag, string $style): string {
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

  /**
   * @param string $blockContent
   * @return array{imageUrl: string, image: string, caption: string}|null
   */
  private function parseBlockContent(string $blockContent): ?array {
    // Suppress warnings for invalid HTML tags
    libxml_use_internal_errors(true);
    $dom = new \DOMDocument();
    $dom->loadHTML($blockContent);
    $figureTag = $dom->getElementsByTagName('figure')->item(0);
    libxml_clear_errors();

    if (!$figureTag) return null;

    $imgTag = $figureTag->getElementsByTagName('img')->item(0);
    $image = $dom->saveHTML($imgTag);
    if (!$image) return null;

    $figcaption = $figureTag->getElementsByTagName('figcaption')->item(0);
    $figcaptionText = $figcaption ? $dom->saveHTML($figcaption) : '';
    $figcaptionText = str_replace(['<figcaption', '</figcaption>'], ['<span', '</span>'], (string)$figcaptionText);

    return [
      'imageUrl' => $imgTag ? $imgTag->getAttribute('src') : '',
      'image' => $image,
      'caption' => $figcaptionText ?: '',
    ];
  }
}

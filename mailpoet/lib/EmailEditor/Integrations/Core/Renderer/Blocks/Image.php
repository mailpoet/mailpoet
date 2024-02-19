<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;
use MailPoet\EmailEditor\Integrations\Utils\DomDocumentHelper;

class Image implements BlockRenderer {
  public function render($blockContent, array $parsedBlock, SettingsController $settingsController): string {
    $parsedHtml = $this->parseBlockContent($blockContent);

    if (!$parsedHtml) {
      return '';
    }

    $imageUrl = $parsedHtml['imageUrl'];
    $image = $parsedHtml['image'];
    $caption = $parsedHtml['caption'];

    $parsedBlock = $this->addImageSizeWhenMissing($parsedBlock, $imageUrl, $settingsController);
    $image = $this->applyRoundedStyle($image, $parsedBlock);
    $image = $this->addImageDimensions($image, $parsedBlock, $settingsController);
    $image = $this->applyImageBorderStyle($image, $parsedBlock, $settingsController);

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
  private function addImageSizeWhenMissing(array $parsedBlock, string $imageUrl, SettingsController $settingsController): array {
    if (!isset($parsedBlock['attrs']['width'])) {
      $maxWidth = $settingsController->parseNumberFromStringWithPixels($parsedBlock['email_attrs']['width'] ?? SettingsController::EMAIL_WIDTH);
      $imageSize = wp_getimagesize($imageUrl);
      $imageSize = $imageSize ? $imageSize[0] : $maxWidth;
      $parsedBlock['attrs']['width'] = min($imageSize, $maxWidth) . 'px';
    }
    return $parsedBlock;
  }

  private function applyImageBorderStyle(string $blockContent, array $parsedBlock, SettingsController $settingsController): string {
    // Getting individual border properties
    $borderColor = $parsedBlock['attrs']['style']['border']['color'] ?? '#000000';
    $borderWidth = $parsedBlock['attrs']['style']['border']['width'] ?? '0px';
    $borderRadius = $parsedBlock['attrs']['style']['border']['radius'] ?? '0px';
    // Because borders can by configured individually, we need to get each one of them and use the main border properties as fallback
    $borderBottomColor = $parsedBlock['attrs']['style']['border']['bottom']['color'] ?? $borderColor;
    $borderLeftColor = $parsedBlock['attrs']['style']['border']['left']['color'] ?? $borderColor;
    $borderRightColor = $parsedBlock['attrs']['style']['border']['right']['color'] ?? $borderColor;
    $borderTopColor = $parsedBlock['attrs']['style']['border']['top']['color'] ?? $borderColor;
    $borderBottomWidth = $parsedBlock['attrs']['style']['border']['bottom']['width'] ?? $borderWidth;
    $borderLeftWidth = $parsedBlock['attrs']['style']['border']['left']['width'] ?? $borderWidth;
    $borderRightWidth = $parsedBlock['attrs']['style']['border']['right']['width'] ?? $borderWidth;
    $borderTopWidth = $parsedBlock['attrs']['style']['border']['top']['width'] ?? $borderWidth;
    $borderBottomLeftRadius = $parsedBlock['attrs']['style']['border']['radius']['bottomLeft'] ?? $borderRadius;
    $borderBottomRightRadius = $parsedBlock['attrs']['style']['border']['radius']['bottomRight'] ?? $borderRadius;
    $borderTopLeftRadius = $parsedBlock['attrs']['style']['border']['radius']['topLeft'] ?? $borderRadius;
    $borderTopRightRadius = $parsedBlock['attrs']['style']['border']['radius']['topRight'] ?? $borderRadius;

    $styles = [
      'border-bottom' => $borderBottomWidth . ' solid ' . $borderBottomColor,
      'border-left' => $borderLeftWidth . ' solid ' . $borderLeftColor,
      'border-top' => $borderTopWidth . ' solid ' . $borderTopColor,
      'border-right' => $borderRightWidth . ' solid ' . $borderRightColor,
      'border-radius' => $borderTopLeftRadius . ' ' . $borderTopRightRadius . ' ' . $borderBottomRightRadius . ' ' . $borderBottomLeftRadius,
    ];
    return $this->addStyleToElement($blockContent, ['tag_name' => 'img'], $settingsController->convertStylesToString($styles));
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
      if ($height && $height !== 'auto' && is_numeric($settingsController->parseNumberFromStringWithPixels($height))) {
        $height = $settingsController->parseNumberFromStringWithPixels($height);
        $html->set_attribute('height', $height);
      }

      if (isset($parsedBlock['attrs']['width'])) {
        $width = $settingsController->parseNumberFromStringWithPixels($parsedBlock['attrs']['width']);
        $html->set_attribute('width', $width);
      }
      $blockContent = $html->get_updated_html();
    }

    return $blockContent;
  }

  /**
   * This method configure the font size of the caption because it's set to 0 for the parent element to avoid unexpected white spaces
   * We try to use font-size passed down from the parent element $parsedBlock['email_attrs']['font-size'], but if it's not set, we use the default font-size from the email theme.
   */
  private function getCaptionStyles(SettingsController $settingsController, array $parsedBlock): string {
    $themeData = $settingsController->getTheme()->get_data();

    // If the alignment is set, we need to center the caption
    $styles = [
      'text-align' => isset($parsedBlock['attrs']['align']) ? 'center' : 'left',
    ];

    $styles['font-size'] = $parsedBlock['email_attrs']['font-size'] ?? $themeData['styles']['typography']['fontSize'];
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
    $marginTop = $parsedBlock['email_attrs']['margin-top'] ?? '0px';

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
                <td style="padding-top:' . $marginTop . '">{image_content}</td>
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
    // If block's image is not set, we don't need to parse the content
    if (empty($blockContent)) return null;

    $domHelper = new DomDocumentHelper($blockContent);

    $figureTag = $domHelper->findElement('figure');
    if (!$figureTag) return null;

    $imgTag = $domHelper->findElement('img');
    if (!$imgTag) return null;

    $imageSrc = $domHelper->getAttributeValue($imgTag, 'src');
    $imageHtml = $domHelper->getOuterHtml($imgTag);

    $figcaption = $domHelper->findElement('figcaption');
    $figcaptionHtml = $figcaption ? $domHelper->getOuterHtml($figcaption) : '';
    $figcaptionHtml = str_replace(['<figcaption', '</figcaption>'], ['<span', '</span>'], $figcaptionHtml);


    return [
      'imageUrl' => $imageSrc ?: '',
      'image' => $imageHtml,
      'caption' => $figcaptionHtml ?: '',
    ];
  }
}

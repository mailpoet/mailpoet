<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\SettingsController;
use MailPoet\EmailEditor\Integrations\Utils\DomDocumentHelper;

class Image extends AbstractBlockRenderer {
  protected function renderContent($blockContent, array $parsedBlock, SettingsController $settingsController): string {
    $parsedHtml = $this->parseBlockContent($blockContent);

    if (!$parsedHtml) {
      return '';
    }

    $imageUrl = $parsedHtml['imageUrl'];
    $image = $parsedHtml['image'];
    $caption = $parsedHtml['caption'];
    $class = $parsedHtml['class'];
    $parsedBlock = $this->addImageSizeWhenMissing($parsedBlock, $imageUrl, $settingsController);
    $image = $this->addImageDimensions($image, $parsedBlock, $settingsController);

    $imageWithWrapper = str_replace(
      ['{image_content}', '{caption_content}'],
      [$image, $caption],
      $this->getBlockWrapper($parsedBlock, $settingsController, $caption)
    );

    $imageWithWrapper = $this->applyRoundedStyle($imageWithWrapper, $parsedBlock);
    $imageWithWrapper = $this->applyImageBorderStyle($imageWithWrapper, $parsedBlock, $class);
    return $imageWithWrapper;
  }

  private function applyRoundedStyle(string $blockContent, array $parsedBlock): string {
    // Because the isn't an attribute for definition of rounded style, we have to check the class name
    if (isset($parsedBlock['attrs']['className']) && strpos($parsedBlock['attrs']['className'], 'is-style-rounded') !== false) {
      // If the image should be in a circle, we need to set the border-radius to 9999px to make it the same as is in the editor
      // This style is applied to both wrapper and the image
      $blockContent = $this->removeStyleAttributeFromElement($blockContent, ['tag_name' => 'td', 'class_name' => 'email-image-cell'], 'border-radius');
      $blockContent = $this->addStyleToElement($blockContent, ['tag_name' => 'td', 'class_name' => 'email-image-cell'], 'border-radius: 9999px;');
      $blockContent = $this->removeStyleAttributeFromElement($blockContent, ['tag_name' => 'img'], 'border-radius');
      $blockContent = $this->addStyleToElement($blockContent, ['tag_name' => 'img'], 'border-radius: 9999px;');
    }
    return $blockContent;
  }

  /**
   * When the width is not set, it's important to get it for the image to be displayed correctly
   */
  private function addImageSizeWhenMissing(array $parsedBlock, string $imageUrl, SettingsController $settingsController): array {
    if (isset($parsedBlock['attrs']['width'])) {
      return $parsedBlock;
    }
    // Can't determine any width let's go with 100%
    if (!isset($parsedBlock['email_attrs']['width'])) {
      $parsedBlock['attrs']['width'] = '100%';
    }
    $maxWidth = $settingsController->parseNumberFromStringWithPixels($parsedBlock['email_attrs']['width']);
    $imageSize = wp_getimagesize($imageUrl);
    $imageSize = $imageSize ? $imageSize[0] : $maxWidth;
    $width = min($imageSize, $maxWidth);
    $parsedBlock['attrs']['width'] = "{$width}px";
    return $parsedBlock;

  }

  private function applyImageBorderStyle(string $blockContent, array $parsedBlock, string $class): string {

    // Getting individual border properties
    $borderStyles = wp_style_engine_get_styles(['border' => $parsedBlock['attrs']['style']['border'] ?? []]);
    $borderStyles = $borderStyles['declarations'] ?? [];
    if (!empty($borderStyles)) {
      $borderStyles['border-style'] = 'solid';
      $borderStyles['box-sizing'] = 'border-box';
    }
    $borderElementTag = ['tag_name' => 'td', 'class_name' => 'email-image-cell'];
    $contentWithBorderStyles = $this->addStyleToElement($blockContent, $borderElementTag, \WP_Style_Engine::compile_css($borderStyles, ''));
    // Add Border related classes to proper element. This is required for inlined border-color styles when defined via class
    $borderClasses = array_filter(explode(' ', $class), function($className) {
      return strpos($className, 'border') !== false;
    });
    $html = new \WP_HTML_Tag_Processor($contentWithBorderStyles);
    if ($html->next_tag($borderElementTag)) {
      $class = $html->get_attribute('class') ?? '';
      $borderClasses[] = $class;
      $html->set_attribute('class', implode(' ', $borderClasses));
    }
    return $html->get_updated_html();
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
        $html->set_attribute('height', esc_attr($height));
      }

      if (isset($parsedBlock['attrs']['width'])) {
        $width = $settingsController->parseNumberFromStringWithPixels($parsedBlock['attrs']['width']);
        $html->set_attribute('width', esc_attr($width));
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

    $styles = [
      'text-align' => isset($parsedBlock['attrs']['align']) ? 'center' : 'left',
    ];

    $styles['font-size'] = $parsedBlock['email_attrs']['font-size'] ?? $themeData['styles']['typography']['fontSize'];
    return \WP_Style_Engine::compile_css($styles, '');
  }

  /**
   * Based on MJML <mj-image> but because MJML doesn't support captions, our solution is a bit different
   */
  private function getBlockWrapper(array $parsedBlock, SettingsController $settingsController, ?string $caption): string {
    $styles = [
      'border-collapse' => 'collapse',
      'border-spacing' => '0px',
      'font-size' => '0px',
      'vertical-align' => 'top',
      'width' => '100%',
    ];

    $width = $parsedBlock['attrs']['width'] ?? '100%';
    $wrapperWidth = ($width && $width !== '100%') ? $width : 'auto';
    $wrapperStyles = $styles;
    $wrapperStyles['width'] = $wrapperWidth;
    $wrapperStyles['border-collapse'] = 'separate'; // Needed because of border radius

    $captionHtml = '';
    if ($caption) {
      // When the image is not aligned, the wrapper is set to 100% width due to caption that can be longer than the image
      $captionWidth = isset($parsedBlock['attrs']['align']) ? ($parsedBlock['attrs']['width'] ?? '100%') : '100%';
      $captionWrapperStyles = $styles;
      $captionWrapperStyles['width'] = $captionWidth;
      $captionStyles = $this->getCaptionStyles($settingsController, $parsedBlock);
      $captionHtml = '
      <table
        role="presentation"
        class="email-table-with-width"
        border="0"
        cellpadding="0"
        cellspacing="0"
        style="' . esc_attr(\WP_Style_Engine::compile_css($captionWrapperStyles, '')) . '"
        width="' . esc_attr($captionWidth) . '"
          >
        <tr>
            <td style="' . esc_attr($captionStyles) . '">{caption_content}</td>
         </tr>
      </table>';
    }

    $styles['width'] = '100%';
    $align = $parsedBlock['attrs']['align'] ?? 'left';

    return '
      <table
        role="presentation"
        border="0"
        cellpadding="0"
        cellspacing="0"
        style="' . esc_attr(\WP_Style_Engine::compile_css($styles, '')) . '"
        width="100%"
      >
        <tr>
          <td align="' . esc_attr($align) . '">
            <table
              role="presentation"
              class="email-table-with-width"
              border="0"
              cellpadding="0"
              cellspacing="0"
              style="' . esc_attr(\WP_Style_Engine::compile_css($wrapperStyles, '')) . '"
              width="' . esc_attr($wrapperWidth) . '"
            >
              <tr>
                <td class="email-image-cell">{image_content}</td>
              </tr>
            </table>' . $captionHtml . '
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
      $elementStyle = $html->get_attribute('style') ?? '';
      $elementStyle = !empty($elementStyle) ? (rtrim($elementStyle, ';') . ';') : ''; // Adding semicolon if it's missing
      $elementStyle .= $style;
      $html->set_attribute('style', esc_attr($elementStyle));
      $blockContent = $html->get_updated_html();
    }

    return $blockContent;
  }

  /**
   * @param array{tag_name: string, class_name?: string} $tag
   */
  private function removeStyleAttributeFromElement($blockContent, array $tag, string $styleName): string {
    $html = new \WP_HTML_Tag_Processor($blockContent);
    if ($html->next_tag($tag)) {
      $elementStyle = $html->get_attribute('style') ?? '';
      $elementStyle = preg_replace('/' . $styleName . ':(.?[0-9]+px)+;?/', '', $elementStyle);
      $html->set_attribute('style', esc_attr($elementStyle));
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
    if (empty($blockContent)) {
      return null;
    }

    $domHelper = new DomDocumentHelper($blockContent);

    $figureTag = $domHelper->findElement('figure');
    if (!$figureTag) {
      return null;
    }

    $imgTag = $domHelper->findElement('img');
    if (!$imgTag) {
      return null;
    }

    $imageSrc = $domHelper->getAttributeValue($imgTag, 'src');
    $imageClass = $domHelper->getAttributeValue($imgTag, 'class');
    $imageHtml = $domHelper->getOuterHtml($imgTag);
    $figcaption = $domHelper->findElement('figcaption');
    $figcaptionHtml = $figcaption ? $domHelper->getOuterHtml($figcaption) : '';
    $figcaptionHtml = str_replace(['<figcaption', '</figcaption>'], ['<span', '</span>'], $figcaptionHtml);

    return [
      'imageUrl' => $imageSrc ?: '',
      'image' => $this->cleanupImageHtml($imageHtml),
      'caption' => $figcaptionHtml ?: '',
      'class' => $imageClass ?: '',
    ];
  }

  private function cleanupImageHtml(string $contentHtml): string {
    $html = new \WP_HTML_Tag_Processor($contentHtml);
    if ($html->next_tag(['tag_name' => 'img'])) {
      $html->remove_attribute('srcset');
      $html->remove_attribute('class');
    }
    return $html->get_updated_html();
  }
}

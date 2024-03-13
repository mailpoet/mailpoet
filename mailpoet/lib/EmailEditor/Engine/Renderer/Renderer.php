<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

use MailPoet\Config\ServicesChecker;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\ContentRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;
use MailPoet\Util\CdnAssetUrl;
use MailPoet\EmailEditor\Engine\ThemeController;
use MailPoet\Util\pQuery\DomNode;
use MailPoetVendor\Html2Text\Html2Text;

class Renderer {
  private \MailPoetVendor\CSS $cssInliner;
  private SettingsController $settingsController;
  private ThemeController $themeController;
  private ContentRenderer $contentRenderer;
  private CdnAssetUrl $cdnAssetUrl;
  private ServicesChecker $servicesChecker;

  const TEMPLATE_FILE = 'template.html';
  const TEMPLATE_STYLES_FILE = 'template.css';

  /**
   * @param \MailPoetVendor\CSS $cssInliner
   */
  public function __construct(
    \MailPoetVendor\CSS $cssInliner,
    SettingsController $settingsController,
    ContentRenderer $contentRenderer,
    CdnAssetUrl $cdnAssetUrl,
    ServicesChecker $servicesChecker,
    ThemeController $themeController
  ) {
    $this->cssInliner = $cssInliner;
    $this->settingsController = $settingsController;
    $this->contentRenderer = $contentRenderer;
    $this->cdnAssetUrl = $cdnAssetUrl;
    $this->servicesChecker = $servicesChecker;
    $this->themeController = $themeController;
  }

  public function render(\WP_Post $post, string $subject, string $preHeader, string $language, $metaRobots = ''): array {
    $layout = $this->settingsController->getLayout();
    $theme = $this->themeController->getTheme();
    $theme = apply_filters('mailpoet_email_editor_rendering_theme_styles', $theme, $post);
    $themeStyles = $theme->get_data()['styles'];
    $padding = $themeStyles['spacing']['padding'];

    $contentBackground = $themeStyles['color']['background']['content'];
    $layoutBackground = $themeStyles['color']['background']['layout'];
    $contentFontFamily = $themeStyles['typography']['fontFamily'];
    $renderedBody = $this->contentRenderer->render($post);

    $styles = (string)file_get_contents(dirname(__FILE__) . '/' . self::TEMPLATE_STYLES_FILE);
    $styles = apply_filters('mailpoet_email_renderer_styles', $styles, $post);

    $template = (string)file_get_contents(dirname(__FILE__) . '/' . self::TEMPLATE_FILE);

    // Replace settings placeholders with values
    $template = str_replace(
      ['{{width}}', '{{layout_background}}', '{{content_background}}', '{{content_font_family}}', '{{padding_top}}', '{{padding_right}}', '{{padding_bottom}}', '{{padding_left}}'],
      [$layout['contentSize'], $layoutBackground, $contentBackground, $contentFontFamily, $padding['top'], $padding['right'], $padding['bottom'], $padding['left']],
      $template
    );

    $logo = $this->cdnAssetUrl->generateCdnUrl('email-editor/logo-footer.png');
    $footerLogo = $this->servicesChecker->isPremiumPluginActive() ? '' : '<img src="' . esc_attr($logo) . '" alt="MailPoet" style="margin: 24px auto; display: block;" />';

    /**
     * Replace template variables
     * {{email_language}}
     * {{email_subject}}
     * {{email_meta_robots}}
     * {{email_template_styles}}
     * {{email_preheader}}
     * {{email_body}}
     * {{email_footer_logo}}
     */
    $templateWithContents = $this->injectContentIntoTemplate(
      $template,
      [
        $language,
        esc_html($subject),
        $metaRobots,
        $styles,
        esc_html($preHeader),
        $renderedBody,
        $footerLogo,
      ]
    );

    $templateWithContentsDom = $this->inlineCSSStyles($templateWithContents);
    $templateWithContents = $this->postProcessTemplate($templateWithContentsDom);
    return [
      'html' => $templateWithContents,
      'text' => $this->renderTextVersion($templateWithContents),
    ];
  }

  private function injectContentIntoTemplate($template, array $content) {
    return preg_replace_callback('/{{\w+}}/', function ($matches) use (&$content) {
      return array_shift($content);
    }, $template);
  }

  /**
   * @param string $template
   * @return DomNode
   */
  private function inlineCSSStyles($template) {
    return $this->cssInliner->inlineCSS($template);
  }

  /**
   * @param string $template
   * @return string
   */
  private function renderTextVersion($template) {
    $template = (mb_detect_encoding($template, 'UTF-8', true)) ? $template : mb_convert_encoding($template, 'UTF-8', mb_list_encodings());
    return @Html2Text::convert($template);
  }

  /**
   * @param DomNode $templateDom
   * @return string
   */
  private function postProcessTemplate(DomNode $templateDom) {
    // because tburry/pquery contains a bug and replaces the opening non mso condition incorrectly we have to replace the opening tag with correct value
    $template = $templateDom->__toString();
    $template = str_replace('<!--[if !mso]><![endif]-->', '<!--[if !mso]><!-- -->', $template);
    return $template;
  }
}

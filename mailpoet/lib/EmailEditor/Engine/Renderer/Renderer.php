<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

use MailPoet\Config\ServicesChecker;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\ContentRenderer;
use MailPoet\EmailEditor\Engine\Renderer\Templates\Templates;
use MailPoet\EmailEditor\Engine\SettingsController;
use MailPoet\EmailEditor\Engine\ThemeController;
use MailPoet\Util\CdnAssetUrl;
use MailPoet\Util\pQuery\DomNode;
use MailPoetVendor\CSS as CssInliner;
use MailPoetVendor\Html2Text\Html2Text;

class Renderer {
  private CssInliner $cssInliner;
  private SettingsController $settingsController;
  private ContentRenderer $contentRenderer;
  private CdnAssetUrl $cdnAssetUrl;
  private ServicesChecker $servicesChecker;
  private Templates $templates;
  private ThemeController $themeController;

  const TEMPLATE_FILE = 'template-canvas.php';
  const TEMPLATE_STYLES_FILE = 'template-canvas.css';

  public function __construct(
    CssInliner $cssInliner,
    SettingsController $settingsController,
    ContentRenderer $contentRenderer,
    CdnAssetUrl $cdnAssetUrl,
    Templates $templates,
    ThemeController $themeController,
    ServicesChecker $servicesChecker
  ) {
    $this->cssInliner = $cssInliner;
    $this->settingsController = $settingsController;
    $this->contentRenderer = $contentRenderer;
    $this->cdnAssetUrl = $cdnAssetUrl;
    $this->templates = $templates;
    $this->themeController = $themeController;
    $this->servicesChecker = $servicesChecker;
    $this->themeController = $themeController;
  }

  public function render(\WP_Post $post, string $subject, string $preHeader, string $language, $metaRobots = ''): array {
    $layout = $this->settingsController->getLayout();
    $themeStyles = $this->settingsController->getEmailStyles();
    $width = $layout['contentSize'];
    $paddingTop = $themeStyles['spacing']['padding']['top'] ?? '0px';
    $paddingBottom = $themeStyles['spacing']['padding']['bottom'] ?? '0px';
    $contentBackground = $themeStyles['color']['background']['content'];
    $layoutBackground = $themeStyles['color']['background']['layout'];
    $contentFontFamily = $themeStyles['typography']['fontFamily'];
    $logoHtml = $this->servicesChecker->isPremiumPluginActive() ? '' : '<img src="' . esc_attr($this->cdnAssetUrl->generateCdnUrl('email-editor/logo-footer.png')) . '" alt="MailPoet" style="margin: 24px auto; display: block;" />';

    $templateStyles = file_get_contents(dirname(__FILE__) . '/' . self::TEMPLATE_STYLES_FILE);
    $templateStyles = apply_filters('mailpoet_email_renderer_styles', $templateStyles . $this->themeController->getStylesheetForRendering(), $post);
    $templateHtml = $this->contentRenderer->render($post, $this->templates->getBlockTemplateFromFile('email-general.html'));

    ob_start();
    include self::TEMPLATE_FILE;
    $renderedTemplate = (string)ob_get_clean();
    $renderedTemplate = $this->inlineCSSStyles($renderedTemplate);
    $renderedTemplate = $this->postProcessTemplate($renderedTemplate);

    return [
      'html' => $renderedTemplate,
      'text' => $this->renderTextVersion($renderedTemplate),
    ];
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

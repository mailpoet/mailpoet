<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

use MailPoet\Config\ServicesChecker;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\ContentRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;
use MailPoet\EmailEditor\Engine\Templates\Templates;
use MailPoet\Util\CdnAssetUrl;
use MailPoetVendor\Html2Text\Html2Text;
use MailPoetVendor\Pelago\Emogrifier\CssInliner;
use WP_Style_Engine;

class Renderer {
  private SettingsController $settingsController;
  private ContentRenderer $contentRenderer;
  private CdnAssetUrl $cdnAssetUrl;
  private ServicesChecker $servicesChecker;
  private Templates $templates;

  const TEMPLATE_FILE = 'template-canvas.php';
  const TEMPLATE_STYLES_FILE = 'template-canvas.css';

  public function __construct(
    SettingsController $settingsController,
    ContentRenderer $contentRenderer,
    CdnAssetUrl $cdnAssetUrl,
    Templates $templates,
    ServicesChecker $servicesChecker
  ) {
    $this->settingsController = $settingsController;
    $this->contentRenderer = $contentRenderer;
    $this->cdnAssetUrl = $cdnAssetUrl;
    $this->templates = $templates;
    $this->servicesChecker = $servicesChecker;
  }

  public function render(\WP_Post $post, string $subject, string $preHeader, string $language, $metaRobots = ''): array {
    ob_start();
    $logoHtml = $this->servicesChecker->isPremiumPluginActive() ? '' : '<img src="' . esc_attr($this->cdnAssetUrl->generateCdnUrl('email-editor/logo-footer.png')) . '" alt="MailPoet" style="margin: 24px auto; display: block;" />';
    $templateHtml = $this->contentRenderer->render($post, $this->templates->getBlockTemplate('mailpoet/mailpoet//email-general'));
    include self::TEMPLATE_FILE;
    $renderedTemplate = (string)ob_get_clean();

    $emailStyles = $this->settingsController->getEmailStyles();
    $templateStyles = WP_Style_Engine::compile_css(
      [
        'background-color' => $emailStyles['color']['background'] ?? 'inherit',
        'padding-top' => $emailStyles['spacing']['padding']['top'] ?? '0px',
        'padding-bottom' => $emailStyles['spacing']['padding']['bottom'] ?? '0px',
        'font-family' => $emailStyles['typography']['fontFamily'] ?? 'inherit',
      ],
      'body, .email_layout_wrapper'
    );
    $templateStyles .= file_get_contents(dirname(__FILE__) . '/' . self::TEMPLATE_STYLES_FILE);
    $renderedTemplate = $this->inlineCSSStyles('<style>' . (string)apply_filters('mailpoet_email_renderer_styles', $templateStyles, $post) . '</style>' . $renderedTemplate);

    return [
      'html' => $renderedTemplate,
      'text' => $this->renderTextVersion($renderedTemplate),
    ];
  }

  /**
   * @param string $template
   * @return string
   */
  private function inlineCSSStyles($template) {
    return CssInliner::fromHtml($template)->inlineCss()->render();
  }

  /**
   * @param string $template
   * @return string
   */
  private function renderTextVersion($template) {
    $template = (mb_detect_encoding($template, 'UTF-8', true)) ? $template : mb_convert_encoding($template, 'UTF-8', mb_list_encodings());
    return @Html2Text::convert($template);
  }
}

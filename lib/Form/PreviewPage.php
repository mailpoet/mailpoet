<?php

namespace MailPoet\Form;

use MailPoet\Config\Renderer as TemplateRenderer;
use MailPoet\Entities\FormEntity;
use MailPoet\WP\Functions as WPFunctions;

class PreviewPage {
  const PREVIEW_DATA_TRANSIENT_PREFIX = 'mailpoet_form_preview_';
  const PREVIEW_DATA_EXPIRATION = 84600; // 1 DAY

  /** @var WPFunctions  */
  private $wp;

  /** @var Renderer */
  private $formRenderer;

  /** @var TemplateRenderer */
  private $templateRenderer;

  /** @var FormsRepository */
  private $formRepository;

  /** @var AssetsController */
  private $assetsController;

  public function __construct(
    WPFunctions $wp,
    Renderer $formRenderer,
    TemplateRenderer $templateRenderer,
    FormsRepository $formRepository,
    AssetsController $assetsController
  ) {
    $this->wp = $wp;
    $this->formRenderer = $formRenderer;
    $this->templateRenderer = $templateRenderer;
    $this->formRepository = $formRepository;
    $this->assetsController = $assetsController;
  }

  public function renderPage(int $formId, string $formType, string $editorUrl): string {
    $this->assetsController->setupFormPreviewDependencies();
    $formData = $this->fetchFormData($formId);
    if (!is_array($formData)) {
      return '';
    }
    return $this->templateRenderer->render(
      'form/form_preview.html',
      [
        'post' => $this->getPostContent(),
        'form' => $this->getFormContent($formData, $formId, $formType, $editorUrl),
        'formType' => $formType,
      ]
    );
  }

  public function renderTitle($title = null, $id = null) {
    if ($id !== $this->wp->getTheId()) {
      return $title;
    }
    return __('Sample page to preview your form', 'mailpoet');
  }

  /**
   * @return array|null
   */
  private function fetchFormData(int $id) {
    $formData = $this->wp->getTransient(self::PREVIEW_DATA_TRANSIENT_PREFIX . $id);
    if (is_array($formData)) {
      return $formData;
    }
    $form = $this->formRepository->findOneById($id);
    if ($form) {
      return [
        'body' => $form->getBody(),
        'styles' => $form->getStyles(),
        'settings' => $form->getSettings(),
      ];
    }
    return null;
  }

  private function getFormContent(array $formData, int $formId, string $formDisplayType, string $editorUrl): string {
    $htmlId = 'mailpoet_form_preview_' . $formId;
    $templateData = [
      'is_preview' => true,
      'editor_url' => $editorUrl,
      'form_html_id' => $htmlId,
      'form_id' => $formId,
      'form_success_message' => $formData['settings']['success_message'] ?? null,
      'form_type' => $formDisplayType,
      'styles' => $this->formRenderer->renderStyles($formData, '#' . $htmlId, $formDisplayType),
      'html' => $this->formRenderer->renderHTML($formData),
      'success' => $formDisplayType === FormEntity::DISPLAY_TYPE_OTHERS,
      'error' => $formDisplayType === FormEntity::DISPLAY_TYPE_OTHERS,
      'delay' => 1,
      'position' => $formData['settings'][$formDisplayType . '_form_position'] ?? '',
      'backgroundColor' => $formData['settings']['backgroundColor'] ?? '',
      'fontFamily' => $formData['settings']['font_family'] ?? '',
    ];
    $formPosition = $formData['settings'][$formDisplayType . '_form_position'] ?? '';
    if (!$formPosition && $formDisplayType === FormEntity::DISPLAY_TYPE_FIXED_BAR) {
      $formPosition = 'top';
    }
    if (!$formPosition && $formDisplayType === FormEntity::DISPLAY_TYPE_SLIDE_IN) {
      $formPosition = 'right';
    }
    $templateData['position'] = $formPosition;
    return $this->templateRenderer->render('form/front_end_form.html', $templateData);
  }

  private function getPostContent(): string {
    $posts = $this->wp->getPosts([
      'numberposts' => 1,
      'orderby' => 'date',
      'order' => 'DESC',
      'post_status' => 'publish',
      'post_type' => 'post',
    ]);
    if (!isset($posts[0])) {
      return '';
    }
    return $posts[0]->post_content;
  }
}

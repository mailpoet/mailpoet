<?php

namespace MailPoet\Form;

use MailPoet\Config\Renderer as TemplateRenderer;
use MailPoet\WP\Functions as WPFunctions;

class PreviewPage {
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

  public function renderPage(int $formId, string $formType): string {
    $this->assetsController->setupFrontEndDependencies();
    $formData = $this->fetchFormData($formId);
    if (!is_array($formData)) {
      return '';
    }
    return $this->getPostContent() . $this->getFormContent($formData, $formId, $formType);
  }

  public function renderTitle() {
    return __('Sample page to preview your form', 'mailpoet');
  }

  /**
   * @return array|null
   */
  private function fetchFormData(int $id) {
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

  private function getFormContent(array $formData, int $formId, string $formType): string {
    $htmlId = 'mailpoet_form_preview_' . $formId;
    $templateData = [
      'is_preview' => true,
      'form_html_id' => $htmlId,
      'form_id' => $formId,
      'form_success_message' => $formData['settings']['success_message'] ?? null,
      'form_type' => $formType,
      'styles' => $this->formRenderer->renderStyles($formData, '#' . $htmlId),
      'html' => $this->formRenderer->renderHTML($formData),
      'form_element_styles' => $this->formRenderer->renderFormElementStyles($formData),
      'success' => true,
      'error' => true,
      'delay' => 1,
      'position' => $formData['settings'][$formType . '_form_position'] ?? '',
      'backgroundColor' => $formData['settings']['backgroundColor'] ?? '',
    ];
    $formPosition = $formData['settings'][$formType . '_form_position'] ?? '';
    if (!$formPosition && $formType === 'fixed_bar') {
      $formPosition = 'top';
    }
    if (!$formPosition && $formType === 'slide_in') {
      $formPosition = 'left';
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

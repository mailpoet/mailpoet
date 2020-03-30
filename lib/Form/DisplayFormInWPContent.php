<?php

namespace MailPoet\Form;

use MailPoet\API\JSON\API;
use MailPoet\Config\Renderer as TemplateRenderer;
use MailPoet\Entities\FormEntity;
use MailPoet\Util\Security;
use MailPoet\WP\Functions as WPFunctions;

class DisplayFormInWPContent {

  const NO_FORM_TRANSIENT_KEY = 'no_forms_displayed_bellow_content';

  /** @var WPFunctions */
  private $wp;

  /** @var FormsRepository */
  private $formsRepository;

  /** @var Renderer */
  private $formRenderer;

  /** @var AssetsController */
  private $assetsController;

  /** @var TemplateRenderer */
  private $templateRenderer;

  public function __construct(
    WPFunctions $wp,
    FormsRepository $formsRepository,
    Renderer $formRenderer,
    AssetsController $assetsController,
    TemplateRenderer $templateRenderer
  ) {
    $this->wp = $wp;
    $this->formsRepository = $formsRepository;
    $this->formRenderer = $formRenderer;
    $this->assetsController = $assetsController;
    $this->templateRenderer = $templateRenderer;
  }

  /**
   * This takes input from an action and any plugin or theme can pass anything.
   * We return string for regular content otherwise we just pass thru what comes.
   * @param mixed $content
   * @return string|mixed
   */
  public function display($content = null) {
    if (!is_string($content) || !$this->shouldDisplay()) return $content;

    $forms = $this->getForms();
    if (count($forms) === 0) {
      $this->saveNoForms();
      return $content;
    }

    $this->assetsController->setupFrontEndDependencies();
    $result = $content;
    foreach ($forms as $form) {
      $result .= $this->getContentBellow($form, 'popup');
      $result .= $this->getContentBellow($form, 'below_post');
    }

    return $result;
  }

  private function shouldDisplay(): bool {
    // this code ensures that we display the form only on a page which is related to single post
    if (!$this->wp->isSingle() && !$this->wp->isPage()) return false;
    $cache = $this->wp->getTransient(DisplayFormInWPContent::NO_FORM_TRANSIENT_KEY);
    if (isset($cache[$this->wp->getPostType()])) return false;
    return true;
  }

  private function saveNoForms() {
    $stored = $this->wp->getTransient(DisplayFormInWPContent::NO_FORM_TRANSIENT_KEY);
    if (!is_array($stored)) $stored = [];
    $stored[$this->wp->getPostType()] = true;
    $this->wp->setTransient(DisplayFormInWPContent::NO_FORM_TRANSIENT_KEY, $stored);
  }

  /**
   * @return FormEntity[]
   */
  private function getForms(): array {
    $forms = $this->formsRepository->findBy(['deletedAt' => null]);
    return array_filter($forms, function($form) {
      return (
        $this->shouldDisplayFormBellowContent($form)
        || $this->shouldDisplayPopupForm($form)
      );
    });
  }

  private function getContentBellow(FormEntity $form, string $displayType): string {
    if ($displayType === 'below_post' && !$this->shouldDisplayFormBellowContent($form)) return '';
    if ($displayType === 'popup' && !$this->shouldDisplayPopupForm($form)) return '';
    $formData = [
      'body' => $form->getBody(),
      'styles' => $form->getStyles(),
      'settings' => $form->getSettings(),
    ];
    $formSettings = $form->getSettings();
    $htmlId = 'mp_form_below_' . $form->getId();
    $templateData = [
      'form_html_id' => $htmlId,
      'form_id' => $form->getId(),
      'form_success_message' => $formSettings['success_message'] ?? null,
      'form_type' => $displayType,
      'styles' => $this->formRenderer->renderStyles($formData, '#' . $htmlId),
      'html' => $this->formRenderer->renderHTML($formData),
      'form_element_styles' => $this->formRenderer->renderFormElementStyles($formData),
    ];

    // (POST) non ajax success/error variables
    $templateData['success'] = (
      (isset($_GET['mailpoet_success']))
      &&
      ((int)$_GET['mailpoet_success'] === $form->getId())
    );
    $templateData['error'] = (
      (isset($_GET['mailpoet_error']))
      &&
      ((int)$_GET['mailpoet_error'] === $form->getId())
    );

    $templateData['delay'] = $formSettings['popup_form_delay'] ?? 0;
    $templateData['backgroundColor'] = $formSettings['backgroundColor'] ?? '';

    // generate security token
    $templateData['token'] = Security::generateToken();

    // add API version
    $templateData['api_version'] = API::CURRENT_VERSION;
    return $this->templateRenderer->render('form/front_end_form.html', $templateData);
  }

  private function shouldDisplayFormBellowContent(FormEntity $form): bool {
    $settings = $form->getSettings();
    if (!is_array($settings)) return false;
    if (!isset($settings['place_form_bellow_all_posts'])) return false;
    if (
      ($settings['place_form_bellow_all_posts'] === '1')
      && $this->wp->isSingular('post')
    ) return true;
    if (
      ($settings['place_form_bellow_all_pages'] === '1')
      && $this->wp->isPage()
    ) return true;
    return false;
  }

  private function shouldDisplayPopupForm(FormEntity $form): bool {
    $settings = $form->getSettings();
    if (!is_array($settings)) return false;
    if (!isset($settings['place_popup_form_on_all_pages'])) return false;
    if (
      ($settings['place_popup_form_on_all_posts'] === '1')
      && $this->wp->isSingular('post')
    ) return true;
    if (
      ($settings['place_popup_form_on_all_pages'] === '1')
      && $this->wp->isPage()
    ) return true;
    return false;
  }
}

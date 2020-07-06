<?php

namespace MailPoet\Form;

use MailPoet\API\JSON\API;
use MailPoet\Config\Renderer as TemplateRenderer;
use MailPoet\Entities\FormEntity;
use MailPoet\Util\Security;
use MailPoet\WP\Functions as WPFunctions;

class DisplayFormInWPContent {

  const NO_FORM_TRANSIENT_KEY = 'no_forms_displayed_bellow_content';

  const SETUP = [
    FormEntity::DISPLAY_TYPE_BELOW_POST => [
      'post' => 'place_form_bellow_all_posts',
      'page' => 'place_form_bellow_all_pages',
    ],
    FormEntity::DISPLAY_TYPE_POPUP => [
      'post' => 'place_popup_form_on_all_posts',
      'page' => 'place_popup_form_on_all_pages',
    ],
    FormEntity::DISPLAY_TYPE_FIXED_BAR => [
      'post' => 'place_fixed_bar_form_on_all_posts',
      'page' => 'place_fixed_bar_form_on_all_pages',
    ],
    FormEntity::DISPLAY_TYPE_SLIDE_IN => [
      'post' => 'place_slide_in_form_on_all_posts',
      'page' => 'place_slide_in_form_on_all_pages',
    ],
  ];

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
    foreach ($forms as $displayType => $form) {
      $result .= $this->getContentBellow($form, $displayType);
    }
    return $result;
  }

  private function shouldDisplay(): bool {
    // This is a fix Yoast plugin and Shapely theme compatibility
    // This is to make sure we only display once for each page
    // Yast plugin calls `get_the_excerpt` which also triggers hook `the_content` we don't want to include our form in that
    // Shapely calls the hook `the_content` multiple times on the page as well and we would display popup multiple times - not ideal
    if (!$this->wp->inTheLoop() || !$this->wp->isMainQuery()) {
      return false;
    }
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
   * @return array<string, FormEntity>
   */
  private function getForms(): array {
    $forms = $this->formsRepository->findBy(['deletedAt' => null], ['updatedAt' => 'ASC']);
    $forms = $this->filterOneFormInEachDisplayType($forms);
    return $forms;
  }

  /**
   * @param FormEntity[] $forms
   * @return array<string, FormEntity>
   */
  private function filterOneFormInEachDisplayType($forms): array {
    $formsFiltered = [];
    foreach ($forms as $form) {
      foreach (array_keys(self::SETUP) as $displayType) {
        if ($this->shouldDisplayFormType($form, $displayType)) {
          $formsFiltered[$displayType] = $form;
        }
      }
    }
    return $formsFiltered;
  }

  private function getContentBellow(FormEntity $form, string $displayType): string {
    if (!$this->shouldDisplayFormType($form, $displayType)) return '';
    $formData = [
      'body' => $form->getBody(),
      'styles' => $form->getStyles(),
      'settings' => $form->getSettings(),
    ];
    $formSettings = $form->getSettings();
    $htmlId = 'mp_form_' . $displayType . $form->getId();
    $templateData = [
      'form_html_id' => $htmlId,
      'form_id' => $form->getId(),
      'form_success_message' => $formSettings['success_message'] ?? null,
      'form_type' => $displayType,
      'styles' => $this->formRenderer->renderStyles($formData, '#' . $htmlId, $displayType),
      'html' => $this->formRenderer->renderHTML($formData),
      'close_button_icon' => $formSettings['close_button'] ?? 'round_white',
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

    $templateData['delay'] = $formSettings[$displayType . '_form_delay'] ?? 0;
    $templateData['position'] = $formSettings[$displayType . '_form_position'] ?? '';
    $templateData['backgroundColor'] = $formSettings['backgroundColor'] ?? '';
    $templateData['fontFamily'] = $formSettings['font_family'] ?? '';

    // generate security token
    $templateData['token'] = Security::generateToken();

    // add API version
    $templateData['api_version'] = API::CURRENT_VERSION;
    return $this->templateRenderer->render('form/front_end_form.html', $templateData);
  }

  private function shouldDisplayFormType(FormEntity $form, string $formType): bool {
    $settings = $form->getSettings();
    if (!is_array($settings)) return false;

    $keys = self::SETUP[$formType];
    $key = '';
    if ($this->wp->isSingular('post')) {
      $key = $keys['post'];
    }
    if ($this->wp->isPage()) {
      $key = $keys['page'];
    }

    return (isset($settings[$key]) && ($settings[$key] === '1'));
  }
}

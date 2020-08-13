<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\API\JSON\ResponseBuilders\CustomFieldsResponseBuilder;
use MailPoet\Config\Localizer;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Form\Block;
use MailPoet\Form\FormFactory;
use MailPoet\Form\Renderer as FormRenderer;
use MailPoet\Form\Templates\TemplateRepository;
use MailPoet\Form\Templates\Templates\Template3BelowPages;
use MailPoet\Form\Templates\Templates\Template3FixedBar;
use MailPoet\Form\Templates\Templates\Template3Popup;
use MailPoet\Form\Templates\Templates\Template3SlideIn;
use MailPoet\Form\Templates\Templates\Template3Widget;
use MailPoet\Form\Templates\Templates\Template4BelowPages;
use MailPoet\Form\Templates\Templates\Template4FixedBar;
use MailPoet\Form\Templates\Templates\Template4Popup;
use MailPoet\Form\Templates\Templates\Template4SlideIn;
use MailPoet\Form\Templates\Templates\Template4Widget;
use MailPoet\Form\Templates\Templates\Template6BelowPages;
use MailPoet\Form\Templates\Templates\Template6FixedBar;
use MailPoet\Form\Templates\Templates\Template6Popup;
use MailPoet\Form\Templates\Templates\Template6SlideIn;
use MailPoet\Form\Templates\Templates\Template6Widget;
use MailPoet\Form\Util\CustomFonts;
use MailPoet\Form\Util\Export;
use MailPoet\Models\Form;
use MailPoet\Models\Segment;
use MailPoet\Router\Endpoints\FormPreview;
use MailPoet\Router\Router;
use MailPoet\Settings\Pages;
use MailPoet\WP\Functions as WPFunctions;

class FormEditor {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var CustomFieldsRepository */
  private $customFieldsRepository;

  /** @var CustomFieldsResponseBuilder */
  private $customFieldsResponseBuilder;

  /** @var FormRenderer */
  private $formRenderer;

  /** @var Block\Date */
  private $dateBlock;

  /** @var WPFunctions */
  private $wp;

  /** @var FormFactory */
  private $formsFactory;

  /** @var Localizer */
  private $localizer;

  /** @var TemplateRepository */
  private $templatesRepository;

  private $activeTemplates = [
    Template3BelowPages::ID,
    Template3FixedBar::ID,
    Template3Popup::ID,
    Template3SlideIn::ID ,
    Template3Widget::ID,
    Template4BelowPages::ID,
    Template4FixedBar::ID,
    Template4Popup::ID,
    Template4SlideIn::ID ,
    Template4Widget::ID,
    Template6BelowPages::ID,
    Template6FixedBar::ID,
    Template6Popup::ID,
    Template6SlideIn::ID ,
    Template6Widget::ID,
  ];

  public function __construct(
    PageRenderer $pageRenderer,
    CustomFieldsRepository $customFieldsRepository,
    CustomFieldsResponseBuilder $customFieldsResponseBuilder,
    FormRenderer $formRenderer,
    Block\Date $dateBlock,
    WPFunctions $wp,
    FormFactory $formsFactory,
    Localizer $localizer,
    TemplateRepository $templateRepository
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->customFieldsRepository = $customFieldsRepository;
    $this->customFieldsResponseBuilder = $customFieldsResponseBuilder;
    $this->formRenderer = $formRenderer;
    $this->dateBlock = $dateBlock;
    $this->wp = $wp;
    $this->formsFactory = $formsFactory;
    $this->localizer = $localizer;
    $this->templatesRepository = $templateRepository;
  }

  public function render() {
    if (!isset($_GET['id']) && !isset($_GET['action'])) {
      $this->renderTemplateSelection();
      return;
    }
    if (isset($_GET['action']) && $_GET['action'] === 'create') {
      $this->createForm();
    }
    $form = Form::findOne((int)$_GET['id']);
    if ($form instanceof Form) {
      $form = $form->asArray();
    }
    $form['styles'] = $this->formRenderer->getCustomStyles($form);
    $customFields = $this->customFieldsRepository->findAll();
    $dateTypes = $this->dateBlock->getDateTypes();
    $data = [
      'form' => $form,
      'form_exports' => [
          'php'       => Export::get('php', $form),
          'iframe'    => Export::get('iframe', $form),
          'shortcode' => Export::get('shortcode', $form),
      ],
      'pages' => Pages::getAll(),
      'segments' => Segment::getSegmentsWithSubscriberCount(),
      'styles' => $this->formRenderer->getCustomStyles($form),
      'date_types' => array_map(function ($label, $value) {
        return [
          'label' => $label,
          'value' => $value,
        ];
      }, $dateTypes, array_keys($dateTypes)),
      'date_formats' => $this->dateBlock->getDateFormats(),
      'month_names' => $this->dateBlock->getMonthNames(),
      'sub_menu' => 'mailpoet-forms',
      'custom_fields' => $this->customFieldsResponseBuilder->buildBatch($customFields),
      'preview_page_url' => $this->getPreviewPageUrl(),
      'custom_fonts' => CustomFonts::FONTS,
      'translations' => $this->getGutenbergScriptsTranslations(),
    ];
    $this->wp->wpEnqueueMedia();
    $this->pageRenderer->displayPage('form/editor.html', $data);
  }

  public function renderTemplateSelection() {
    $templateForms = $this->templatesRepository->getFormTemplates($this->activeTemplates);
    $templatesData = [];
    foreach ($templateForms as $templateId => $form) {
      $templatesData[] = [
        'id' => $templateId,
        'name' => $form->getName(),
      ];
    }
    $data = [
      'templates' => $templatesData,
    ];
    $this->pageRenderer->displayPage('form/template_selection.html', $data);
  }

  private function createForm() {
    $form = $this->formsFactory->createEmptyForm();

    $this->wp->wpSafeRedirect(
      $this->wp->getSiteUrl(null,
        '/wp-admin/admin.php?page=mailpoet-form-editor&id=' . $form->getId()
      )
    );
    exit;
  }

  private function getPreviewPageUrl() {
    $mailpoetPage = Pages::getDefaultMailPoetPage();
    if (!$mailpoetPage) {
      return null;
    }
    $url = $this->wp->getPermalink($mailpoetPage);
    $params = [
      Router::NAME,
      'endpoint=' . FormPreview::ENDPOINT,
      'action=' . FormPreview::ACTION_VIEW,
    ];
    $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . join('&', $params);
    return $url;
  }

  /**
   * JS Translations are distributed and loaded per script. We can't use wp_set_script_translations
   * because translation filename is determined based on script filename and path.
   * This function loads JSON files with Gutenberg script's translations distributed within WordPress.
   * Implemented based on load_script_textdomain function
   * @see https://developer.wordpress.org/reference/functions/load_script_textdomain/
   * @return string[]
   */
  private function getGutenbergScriptsTranslations() {
    $locale = $this->localizer->locale();
    if (!$locale) {
      return [];
    }
    // List of scripts - relative path to translations directory (default: wp-content/languages)
    $translationsToLoad = [
      'wp-includes/js/dist/blocks.js',
      'wp-includes/js/dist/components.js',
      'wp-includes/js/dist/block-editor.js',
      'wp-includes/js/dist/block-library.js',
      'wp-includes/js/dist/editor.js',
      'wp-includes/js/dist/media-utils.js',
      'wp-includes/js/dist/format-library.js',
      'wp-includes/js/dist/edit-post.js',
    ];

    $translations = [];
    foreach ($translationsToLoad as $translation) {
      $file = WP_LANG_DIR . '/' . $locale . '-' . md5( $translation ) . '.json';
      if (!file_exists($file)) {
        continue;
      }
      $translationsData = file_get_contents($file);
      if ($translationsData) {
        $translations[] = $translationsData;
      }
    }
    return $translations;
  }
}

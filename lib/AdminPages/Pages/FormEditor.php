<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\API\JSON\ResponseBuilders\CustomFieldsResponseBuilder;
use MailPoet\Config\Localizer;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\FormEntity;
use MailPoet\Form\Block;
use MailPoet\Form\FormFactory;
use MailPoet\Form\Renderer as FormRenderer;
use MailPoet\Form\Templates\TemplateRepository;
use MailPoet\Form\Templates\Templates\Template10BelowPages;
use MailPoet\Form\Templates\Templates\Template10FixedBar;
use MailPoet\Form\Templates\Templates\Template10Popup;
use MailPoet\Form\Templates\Templates\Template10SlideIn;
use MailPoet\Form\Templates\Templates\Template10Widget;
use MailPoet\Form\Templates\Templates\Template11BelowPages;
use MailPoet\Form\Templates\Templates\Template11FixedBar;
use MailPoet\Form\Templates\Templates\Template11Popup;
use MailPoet\Form\Templates\Templates\Template11SlideIn;
use MailPoet\Form\Templates\Templates\Template11Widget;
use MailPoet\Form\Templates\Templates\Template12BelowPages;
use MailPoet\Form\Templates\Templates\Template12FixedBar;
use MailPoet\Form\Templates\Templates\Template12Popup;
use MailPoet\Form\Templates\Templates\Template12SlideIn;
use MailPoet\Form\Templates\Templates\Template12Widget;
use MailPoet\Form\Templates\Templates\Template13BelowPages;
use MailPoet\Form\Templates\Templates\Template13FixedBar;
use MailPoet\Form\Templates\Templates\Template13Popup;
use MailPoet\Form\Templates\Templates\Template13SlideIn;
use MailPoet\Form\Templates\Templates\Template13Widget;
use MailPoet\Form\Templates\Templates\Template14BelowPages;
use MailPoet\Form\Templates\Templates\Template14FixedBar;
use MailPoet\Form\Templates\Templates\Template14Popup;
use MailPoet\Form\Templates\Templates\Template14SlideIn;
use MailPoet\Form\Templates\Templates\Template14Widget;
use MailPoet\Form\Templates\Templates\Template17BelowPages;
use MailPoet\Form\Templates\Templates\Template17FixedBar;
use MailPoet\Form\Templates\Templates\Template17Popup;
use MailPoet\Form\Templates\Templates\Template17SlideIn;
use MailPoet\Form\Templates\Templates\Template17Widget;
use MailPoet\Form\Templates\Templates\Template18BelowPages;
use MailPoet\Form\Templates\Templates\Template18FixedBar;
use MailPoet\Form\Templates\Templates\Template18Popup;
use MailPoet\Form\Templates\Templates\Template18SlideIn;
use MailPoet\Form\Templates\Templates\Template18Widget;
use MailPoet\Form\Templates\Templates\Template1BelowPages;
use MailPoet\Form\Templates\Templates\Template1FixedBar;
use MailPoet\Form\Templates\Templates\Template1Popup;
use MailPoet\Form\Templates\Templates\Template1SlideIn;
use MailPoet\Form\Templates\Templates\Template1Widget;
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
use MailPoet\Form\Templates\Templates\Template7BelowPages;
use MailPoet\Form\Templates\Templates\Template7FixedBar;
use MailPoet\Form\Templates\Templates\Template7Popup;
use MailPoet\Form\Templates\Templates\Template7SlideIn;
use MailPoet\Form\Templates\Templates\Template7Widget;
use MailPoet\Form\Util\CustomFonts;
use MailPoet\Form\Util\Export;
use MailPoet\Models\Form;
use MailPoet\Models\Segment;
use MailPoet\Router\Endpoints\FormPreview;
use MailPoet\Router\Router;
use MailPoet\Settings\Pages;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\UserFlagsController;
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

  /** @var UserFlagsController */
  private $userFlags;

  /** @var SettingsController */
  private $settings;

  private $activeTemplates = [
    FormEntity::DISPLAY_TYPE_POPUP => [
      Template1Popup::ID,
      Template3Popup::ID,
      Template4Popup::ID,
      Template6Popup::ID,
      Template7Popup::ID,
      Template10Popup::ID,
      Template11Popup::ID,
      Template12Popup::ID,
      Template13Popup::ID,
      Template14Popup::ID,
      Template17Popup::ID,
      Template18Popup::ID,
    ],
    FormEntity::DISPLAY_TYPE_SLIDE_IN => [
      Template1SlideIn::ID,
      Template3SlideIn::ID,
      Template4SlideIn::ID,
      Template6SlideIn::ID,
      Template7SlideIn::ID,
      Template10SlideIn::ID,
      Template11SlideIn::ID,
      Template12SlideIn::ID,
      Template13SlideIn::ID,
      Template14SlideIn::ID,
      Template17SlideIn::ID,
      Template18SlideIn::ID,
    ],
    FormEntity::DISPLAY_TYPE_FIXED_BAR => [
      Template1FixedBar::ID,
      Template3FixedBar::ID,
      Template4FixedBar::ID,
      Template6FixedBar::ID,
      Template7FixedBar::ID,
      Template10FixedBar::ID,
      Template11FixedBar::ID,
      Template12FixedBar::ID,
      Template13FixedBar::ID,
      Template14FixedBar::ID,
      Template17FixedBar::ID,
      Template18FixedBar::ID,
    ],
    FormEntity::DISPLAY_TYPE_BELOW_POST => [
      Template1BelowPages::ID,
      Template3BelowPages::ID,
      Template4BelowPages::ID,
      Template6BelowPages::ID,
      Template7BelowPages::ID,
      Template10BelowPages::ID,
      Template11BelowPages::ID,
      Template12BelowPages::ID,
      Template13BelowPages::ID,
      Template14BelowPages::ID,
      Template17BelowPages::ID,
      Template18BelowPages::ID,
    ],
    FormEntity::DISPLAY_TYPE_OTHERS => [
      Template1Widget::ID,
      Template3Widget::ID,
      Template4Widget::ID,
      Template6Widget::ID,
      Template7Widget::ID,
      Template10Widget::ID,
      Template11Widget::ID,
      Template12Widget::ID,
      Template13Widget::ID,
      Template14Widget::ID,
      Template17Widget::ID,
      Template18Widget::ID,
    ],
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
    UserFlagsController $userFlags,
    SettingsController $settings,
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
    $this->userFlags = $userFlags;
    $this->settings = $settings;
  }

  public function render() {
    if (!isset($_GET['id']) && !isset($_GET['action'])) {
      $this->renderTemplateSelection();
      return;
    }
    if (isset($_GET['action']) && $_GET['action'] === 'create') {
      $this->createForm();
    }
    $form = $this->getFormData((int)$_GET['id']);
    $customFields = $this->customFieldsRepository->findAll();
    $dateTypes = $this->dateBlock->getDateTypes();
    $data = [
      'form' => $form,
      'form_exports' => [
          'php'       => Export::get('php', $form),
          'iframe'    => Export::get('iframe', $form),
          'shortcode' => Export::get('shortcode', $form),
      ],
      'mailpoet_pages' => Pages::getMailPoetPages(),
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
      'editor_tutorial_seen' => $this->userFlags->get('form_editor_tutorial_seen'),
      'preview_page_url' => $this->getPreviewPageUrl(),
      'custom_fonts' => CustomFonts::FONTS,
      'translations' => $this->getGutenbergScriptsTranslations(),
      'posts' => $this->getAllPosts(),
      'pages' => $this->getAllPages(),
      'categories' => $this->getAllCategories(),
      'tags' => $this->getAllTags(),
      'products' => $this->getWooCommerceProducts(),
      'product_categories' => $this->getWooCommerceCategories(),
      'product_tags' => $this->getWooCommerceTags(),
      'installed_at' => $this->settings->get('installed_at'),
    ];
    $this->wp->wpEnqueueMedia();
    $this->pageRenderer->displayPage('form/editor.html', $data);
  }

  public function renderTemplateSelection() {
    $templatesData = [];
    foreach ($this->activeTemplates as $formType => $templateIds) {
      $templateForms = $this->templatesRepository->getFormTemplates($this->activeTemplates[$formType]);
      $templatesData[$formType] = [];
      foreach ($templateForms as $templateId => $form) {
        $templatesData[$formType][] = [
          'id' => $templateId,
          'name' => $form->getName(),
          'thumbnail' => $form->getThumbnailUrl(),
        ];
      }
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

  private function getFormData(int $id) {
    $form = Form::findOne($id);
    if (!$form instanceof Form) {
      return null;
    }
    $form = $form->asArray();
    $form['styles'] = $this->formRenderer->getCustomStyles($form);
    // Use empty settings in case they are corrupted or missing
    if (!is_array($form['settings'])) {
      $initialFormTemplate = $this->templatesRepository->getFormTemplate(TemplateRepository::INITIAL_FORM_TEMPLATE);
      $form['settings'] = $initialFormTemplate->getSettings();
    }
    return $form;
  }

  private function getAllPosts() {
    global $wpdb;
    $optionList = $wpdb->get_results('SELECT ID, post_title FROM ' . $wpdb->posts . " WHERE post_type='post'");
    return $this->formatPosts($optionList);
  }

  private function getWooCommerceProducts() {
    global $wpdb;
    $optionList = $wpdb->get_results('SELECT ID, post_title FROM ' . $wpdb->posts . " WHERE post_type='product'");
    return $this->formatPosts($optionList);
  }

  private function getAllPages() {
    global $wpdb;
    $optionList = $wpdb->get_results('SELECT ID, post_title FROM ' . $wpdb->posts . " WHERE post_type='page'");
    return $this->formatPosts($optionList);
  }

  private function getWooCommerceCategories() {
    return $this->formatTerms($this->wp->getCategories(['taxonomy' => 'product_cat']));
  }

  private function getWooCommerceTags() {
    return $this->formatTerms($this->wp->getTerms('product_tag'));
  }

  private function getAllCategories() {
    return $this->formatTerms($this->wp->getCategories());
  }

  private function getAllTags() {
    return $this->formatTerms($this->wp->getTags());
  }

  private function formatPosts($posts) {
    if (empty($posts)) return [];
    $result = [];
    foreach ($posts as $post) {
      $result[] = [
        'id' => $post->ID,
        'name' => $post->post_title,// phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      ];
    }
    return $result;
  }

  private function formatTerms($terms) {
    if (empty($terms)) return [];
    if (!is_array($terms)) return []; // there can be instance of WP_Error instead of list of terms if woo commerce is not active
    $result = [];
    foreach ($terms as $term) {
      $result[] = [
        'id' => $term->term_id,// phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
        'name' => $term->name,
      ];
    }
    return $result;
  }
}

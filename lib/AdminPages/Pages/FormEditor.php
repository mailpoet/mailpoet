<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\API\JSON\ResponseBuilders\CustomFieldsResponseBuilder;
use MailPoet\Config\Localizer;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Form\Block;
use MailPoet\Form\FormFactory;
use MailPoet\Form\Renderer as FormRenderer;
use MailPoet\Form\Util\CustomFonts;
use MailPoet\Form\Util\Export;
use MailPoet\Models\Form;
use MailPoet\Models\Segment;
use MailPoet\Router\Endpoints\FormPreview;
use MailPoet\Router\Router;
use MailPoet\Settings\Pages;
use MailPoet\WP\Functions as WPFunctions;

class FormEditor {
  const TEMPLATES = [
    'my-fancy-template1' => [
      'id' => 'my-fancy-template1',
      'name' => 'My Fancy Form',
      'body' => [
        [
          'type' => 'columns',
          'body' =>
            [
              [
                'type' => 'column',
                'params' => ['class_name' => '', 'vertical_alignment' => '', 'width' => '50'],
                'body' => [
                  [
                    'type' => 'text',
                    'params' => ['label' => 'Email', 'class_name' => '', 'required' => '1'],
                    'id' => 'email',
                    'name' => 'Email',
                    'styles' => ['full_width' => '1'],
                  ],
                  [
                    'type' => 'text',
                    'params' => ['label' => 'First name', 'class_name' => ''],
                    'id' => 'first_name',
                    'name' => 'First name',
                    'styles' => ['full_width' => '1'],
                  ],
                ],
              ],
              [
                'type' => 'column',
                'params' => ['class_name' => '', 'vertical_alignment' => '', 'width' => '50'],
                'body' => [
                  [
                    'type' => 'paragraph',
                    'id' => 'paragraph',
                    'params' => [
                      'content' => 'Far far away, behind the word mountains, far from the countries Vokalia and Consonantia, there live the blind texts. Separated they live in Bookmarksgrove right at the coast of the Semantics, a large language ocean.',
                      'drop_cap' => '0',
                      'align' => 'left',
                      'font_size' => '',
                      'text_color' => '',
                      'background_color' => '',
                      'class_name' => '',
                    ],
                  ],
                ],
              ],
            ],
          'params' => [
            'vertical_alignment' => '',
            'class_name' => '',
            'text_color' => '',
            'background_color' => '',
          ],
        ],
        [
          'type' => 'submit',
          'params' => ['label' => 'Subscribe!', 'class_name' => ''],
          'id' => 'submit',
          'name' => 'Submit',
          'styles' => [
            'full_width' => '0',
            'bold' => '0',
            'background_color' => '#ff6900',
            'font_size' => '36',
            'font_color' => '#313131',
            'border_size' => '1',
            'border_radius' => '8',
            'border_color' => '#f78da7',
            'padding' => '5',
          ],
        ],
      ],
      'settings' => [
        'segments' => [],
        'on_success' => 'message',
        'success_message' => 'Check your inbox or spam folder to confirm your subscription.',
        'success_page' => '5',
        'segments_selected_by' => 'admin',
        'alignment' => 'left',
        'place_form_bellow_all_pages' => '',
        'place_form_bellow_all_posts' => '',
        'place_popup_form_on_all_pages' => '1',
        'place_popup_form_on_all_posts' => '1',
        'popup_form_delay' => '15',
        'place_fixed_bar_form_on_all_pages' => '',
        'place_fixed_bar_form_on_all_posts' => '',
        'fixed_bar_form_delay' => '15',
        'fixed_bar_form_position' => 'top',
        'place_slide_in_form_on_all_pages' => '',
        'place_slide_in_form_on_all_posts' => '',
        'slide_in_form_delay' => '15',
        'slide_in_form_position' => 'right',
        'border_radius' => '0',
        'border_size' => '0',
        'form_padding' => '20',
        'input_padding' => '5',
        'below_post_styles' => ['width' => ['unit' => 'percent', 'value' => '100']],
        'slide_in_styles' => ['width' => ['unit' => 'pixel', 'value' => '560']],
        'fixed_bar_styles' => ['width' => ['unit' => 'percent', 'value' => '100']],
        'popup_styles' => ['width' => ['unit' => 'pixel', 'value' => '560']],
        'other_styles' => ['width' => ['unit' => 'percent', 'value' => '100']],
      ],
      'styles' => '
            /* form */.mailpoet_form {}
            /* columns */.mailpoet_column_with_background {  padding: 10px;}/* space between columns */.mailpoet_form_column:not(:first-child) {  margin-left: 20px;}
            /* input wrapper (label + input) */.mailpoet_paragraph {  line-height:20px;  margin-bottom: 20px;}
            /* labels */.mailpoet_segment_label,.mailpoet_text_label,.mailpoet_textarea_label,.mailpoet_select_label,.mailpoet_radio_label,.mailpoet_checkbox_label,.mailpoet_list_label,.mailpoet_date_label {  display:block;  font-weight: normal;}
            /* inputs */.mailpoet_text,.mailpoet_textarea,.mailpoet_select,.mailpoet_date_month,.mailpoet_date_day,.mailpoet_date_year,.mailpoet_date {  display:block;}
            .mailpoet_text,.mailpoet_textarea {  width: 200px;}
            .mailpoet_checkbox {}
            .mailpoet_submit {}
            .mailpoet_divider {}
            .mailpoet_message {}
            .mailpoet_form_loading {  width: 30px;  text-align: center;  line-height: normal;}
            .mailpoet_form_loading > span {  width: 5px;  height: 5px;  background-color: #5b5b5b;}
        ',
    ],
  ];

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

  public function __construct(
    PageRenderer $pageRenderer,
    CustomFieldsRepository $customFieldsRepository,
    CustomFieldsResponseBuilder $customFieldsResponseBuilder,
    FormRenderer $formRenderer,
    Block\Date $dateBlock,
    WPFunctions $wp,
    FormFactory $formsFactory,
    Localizer $localizer
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->customFieldsRepository = $customFieldsRepository;
    $this->customFieldsResponseBuilder = $customFieldsResponseBuilder;
    $this->formRenderer = $formRenderer;
    $this->dateBlock = $dateBlock;
    $this->wp = $wp;
    $this->formsFactory = $formsFactory;
    $this->localizer = $localizer;
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
    $templates = array_values(self::TEMPLATES);
    $data = [
      'templates' => $templates,
    ];
    $this->pageRenderer->displayPage('form/template_selection.html', $data);
  }

  private function createForm() {
    $form = $this->formsFactory->createEmptyForm();

    $this->wp->wpSafeRedirect(
      $this->wp->getSiteUrl(null,
        '/wp-admin/admin.php?page=mailpoet-form-editor&id=' . $form->id()
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

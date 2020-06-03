<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\API\JSON\ResponseBuilders\CustomFieldsResponseBuilder;
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

  public function __construct(
    PageRenderer $pageRenderer,
    CustomFieldsRepository $customFieldsRepository,
    CustomFieldsResponseBuilder $customFieldsResponseBuilder,
    FormRenderer $formRenderer,
    Block\Date $dateBlock,
    WPFunctions $wp,
    FormFactory $formsFactory
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->customFieldsRepository = $customFieldsRepository;
    $this->customFieldsResponseBuilder = $customFieldsResponseBuilder;
    $this->formRenderer = $formRenderer;
    $this->dateBlock = $dateBlock;
    $this->wp = $wp;
    $this->formsFactory = $formsFactory;
  }

  public function render() {
    $id = (isset($_GET['id']) ? (int)$_GET['id'] : 0);
    if (isset($_GET['action']) && $_GET['action'] === 'create') {
      $form = $this->formsFactory->createEmptyForm();

      $this->wp->wpSafeRedirect(
        $this->wp->getSiteUrl(null,
          '/wp-admin/admin.php?page=mailpoet-form-editor&id=' . $form->id()
        )
      );
      exit;
    }
    $form = Form::findOne($id);
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
    ];
    $this->wp->wpEnqueueMedia();
    $this->pageRenderer->displayPage('form/editor.html', $data);
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
}

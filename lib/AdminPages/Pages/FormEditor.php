<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\API\JSON\ResponseBuilders\CustomFieldsResponseBuilder;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Features\FeaturesController;
use MailPoet\Form\Block;
use MailPoet\Form\Renderer as FormRenderer;
use MailPoet\Form\Util\Export;
use MailPoet\Models\CustomField;
use MailPoet\Models\Form;
use MailPoet\Models\Segment;
use MailPoet\Settings\Pages;

class FormEditor {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var FeaturesController */
  private $featuresController;

  /** @var CustomFieldsRepository */
  private $customFieldsRepository;

  /** @var CustomFieldsResponseBuilder */
  private $customFieldsResponseBuilder;

  public function __construct(
    PageRenderer $pageRenderer,
    FeaturesController $featuresController,
    CustomFieldsRepository $customFieldsRepository,
    CustomFieldsResponseBuilder $customFieldsResponseBuilder
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->featuresController = $featuresController;
    $this->customFieldsRepository = $customFieldsRepository;
    $this->customFieldsResponseBuilder = $customFieldsResponseBuilder;
  }

  public function render() {
    $id = (isset($_GET['id']) ? (int)$_GET['id'] : 0);
    $form = Form::findOne($id);
    if ($form instanceof Form) {
      $form = $form->asArray();
    }

    $data = [
      'form' => $form,
      'form_exports' => [
          'php'       => Export::get('php', $form),
          'iframe'    => Export::get('iframe', $form),
          'shortcode' => Export::get('shortcode', $form),
      ],
      'pages' => Pages::getAll(),
      'segments' => Segment::getSegmentsWithSubscriberCount(),
      'styles' => FormRenderer::getStyles($form),
      'date_types' => Block\Date::getDateTypes(),
      'date_formats' => Block\Date::getDateFormats(),
      'month_names' => Block\Date::getMonthNames(),
      'sub_menu' => 'mailpoet-forms',
    ];

    if ($this->featuresController->isSupported(FeaturesController::NEW_FORM_EDITOR)) {
      $data['form']['styles'] = FormRenderer::getStyles($form);
      $customFields = $this->customFieldsRepository->findAll();
      $data['custom_fields'] = $this->customFieldsResponseBuilder->buildBatch($customFields);
      $data['date_types'] = array_map(function ($label, $value) {
        return [
          'label' => $label,
          'value' => $value,
        ];
      }, $data['date_types'], array_keys($data['date_types']));
      $this->pageRenderer->displayPage('form/editor.html', $data);
    } else {
      $this->pageRenderer->displayPage('form/editor_legacy.html', $data);
    }
  }
}

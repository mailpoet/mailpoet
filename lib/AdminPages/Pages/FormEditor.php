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
  private $page_renderer;

  /** @var FeaturesController */
  private $features_controller;

  /** @var CustomFieldsRepository */
  private $custom_fields_repository;

  /** @var CustomFieldsResponseBuilder */
  private $custom_fields_response_builder;

  function __construct(
    PageRenderer $page_renderer,
    FeaturesController $features_controller,
    CustomFieldsRepository $custom_fields_repository,
    CustomFieldsResponseBuilder $custom_fields_response_builder
  ) {
    $this->page_renderer = $page_renderer;
    $this->features_controller = $features_controller;
    $this->custom_fields_repository = $custom_fields_repository;
    $this->custom_fields_response_builder = $custom_fields_response_builder;
  }

  function render() {
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

    if ($this->features_controller->isSupported(FeaturesController::NEW_FORM_EDITOR)) {
      $data['form']['styles'] = FormRenderer::getStyles($form);
      $custom_fields = $this->custom_fields_repository->findAll();
      $data['custom_fields'] = $this->custom_fields_response_builder->buildBatch($custom_fields);
      $this->page_renderer->displayPage('form/editor.html', $data);
    } else {
      $this->page_renderer->displayPage('form/editor_legacy.html', $data);
    }

  }
}

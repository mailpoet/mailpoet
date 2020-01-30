<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\API\JSON\ResponseBuilders\CustomFieldsResponseBuilder;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Form\Block;
use MailPoet\Form\Renderer as FormRenderer;
use MailPoet\Form\Util\Export;
use MailPoet\Models\Form;
use MailPoet\Models\Segment;
use MailPoet\Settings\Pages;

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

  public function __construct(
    PageRenderer $pageRenderer,
    CustomFieldsRepository $customFieldsRepository,
    CustomFieldsResponseBuilder $customFieldsResponseBuilder,
    FormRenderer $formRenderer,
    Block\Date $dateBlock
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->customFieldsRepository = $customFieldsRepository;
    $this->customFieldsResponseBuilder = $customFieldsResponseBuilder;
    $this->formRenderer = $formRenderer;
    $this->dateBlock = $dateBlock;
  }

  public function render() {
    $id = (isset($_GET['id']) ? (int)$_GET['id'] : 0);
    $form = Form::findOne($id);
    if ($form instanceof Form) {
      $form = $form->asArray();
    }
    $form['styles'] = $this->formRenderer->getStyles($form);
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
      'styles' => $this->formRenderer->getStyles($form),
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
    ];

    $this->pageRenderer->displayPage('form/editor.html', $data);
  }
}

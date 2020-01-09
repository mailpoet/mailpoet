<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Config\AccessControl;
use MailPoet\Features\FeaturesController;
use MailPoet\Form\Renderer as FormRenderer;
use MailPoet\Form\Util;
use MailPoet\Listing;
use MailPoet\Models\Form;
use MailPoet\Models\StatisticsForms;
use MailPoet\WP\Functions as WPFunctions;

class Forms extends APIEndpoint {

  /** @var Listing\BulkActionController */
  private $bulkAction;

  /** @var Listing\Handler */
  private $listingHandler;

  /** @var FeaturesController */
  private $featuresController;

  /** @var Util\Styles */
  private $formStylesUtils;

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_FORMS,
  ];

  public function __construct(
    Listing\BulkActionController $bulkAction,
    Listing\Handler $listingHandler,
    FeaturesController $featuresController,
    Util\Styles $formStylesUtils
  ) {
    $this->bulkAction = $bulkAction;
    $this->listingHandler = $listingHandler;
    $this->featuresController = $featuresController;
    $this->formStylesUtils = $formStylesUtils;
  }

  public function get($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $form = Form::findOne($id);
    if ($form instanceof Form) {
      return $this->successResponse($form->asArray());
    }
    return $this->errorResponse([
      APIError::NOT_FOUND => WPFunctions::get()->__('This form does not exist.', 'mailpoet'),
    ]);
  }

  public function listing($data = []) {
    $listingData = $this->listingHandler->get('\MailPoet\Models\Form', $data);

    $data = [];
    foreach ($listingData['items'] as $form) {
      $form = $form->asArray();

      $form['signups'] = StatisticsForms::getTotalSignups($form['id']);

      $form['segments'] = (
        !empty($form['settings']['segments'])
        ? $form['settings']['segments']
        : []
      );

      $data[] = $form;
    }

    return $this->successResponse($data, [
      'count' => $listingData['count'],
      'filters' => $listingData['filters'],
      'groups' => $listingData['groups'],
    ]);
  }

  public function create() {
    $formName = WPFunctions::get()->__('New form', 'mailpoet');
    if ($this->featuresController->isSupported(FeaturesController::NEW_FORM_EDITOR)) {
      $formName = '';
    }
    // create new form
    $formData = [
      'name' => $formName,
      'body' => [
        [
          'id' => 'email',
          'name' => WPFunctions::get()->__('Email', 'mailpoet'),
          'type' => 'text',
          'static' => true,
          'params' => [
            'label' => WPFunctions::get()->__('Email', 'mailpoet'),
            'required' => true,
          ],
        ],
        [
          'id' => 'submit',
          'name' => WPFunctions::get()->__('Submit', 'mailpoet'),
          'type' => 'submit',
          'static' => true,
          'params' => [
            'label' => WPFunctions::get()->__('Subscribe!', 'mailpoet'),
          ],
        ],
      ],
      'settings' => [
        'on_success' => 'message',
        'success_message' => Form::getDefaultSuccessMessage(),
        'segments' => null,
        'segments_selected_by' => 'admin',
      ],
    ];

    if ($this->featuresController->isSupported(FeaturesController::NEW_FORM_EDITOR)) {
      $formData['body'][0]['params']['label_within'] = true;
    }

    return $this->save($formData);
  }

  public function save($data = []) {
    $form = Form::createOrUpdate($data);
    $errors = $form->getErrors();

    if (empty($errors)) {
      $form = Form::findOne($form->id);
      if(!$form instanceof Form) return $this->errorResponse();
      return $this->successResponse($form->asArray());
    }
    return $this->badRequest($errors);
  }

  public function previewEditor($data = []) {
    // html
    $html = FormRenderer::renderHTML($data);

    // convert shortcodes
    $html = WPFunctions::get()->doShortcode($html);

    // styles
    $css = $this->formStylesUtils->render(FormRenderer::getStyles($data));

    return $this->successResponse([
      'html' => $html,
      'css' => $css,
    ]);
  }

  public function exportsEditor($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $form = Form::findOne($id);
    if ($form instanceof Form) {
      $exports = Util\Export::getAll($form->asArray());
      return $this->successResponse($exports);
    }
    return $this->errorResponse([
      APIError::NOT_FOUND => WPFunctions::get()->__('This form does not exist.', 'mailpoet'),
    ]);
  }

  public function saveEditor($data = []) {
    $formId = (isset($data['id']) ? (int)$data['id'] : 0);
    $name = (isset($data['name']) ? $data['name'] : WPFunctions::get()->__('New form', 'mailpoet'));
    $body = (isset($data['body']) ? $data['body'] : []);
    $settings = (isset($data['settings']) ? $data['settings'] : []);
    $styles = (isset($data['styles']) ? $data['styles'] : '');

    // check if the form is used as a widget
    $isWidget = false;
    $widgets = WPFunctions::get()->getOption('widget_mailpoet_form');
    if (!empty($widgets)) {
      foreach ($widgets as $widget) {
        if (isset($widget['form']) && (int)$widget['form'] === $formId) {
          $isWidget = true;
          break;
        }
      }
    }

    // check if the user gets to pick his own lists
    // or if it's selected by the admin
    $hasSegmentSelection = false;
    $listSelection = [];
    foreach ($body as $i => $block) {
      if ($block['type'] === 'segment') {
        $hasSegmentSelection = true;
        if (!empty($block['params']['values'])) {
          $listSelection = array_filter(
            array_map(function($segment) {
              return (isset($segment['id'])
                ? $segment['id']
                : null
              );
            }, $block['params']['values'])
          );
        }
        break;
      }
    }

    // check list selection
    if ($hasSegmentSelection === true) {
      $settings['segments_selected_by'] = 'user';
      $settings['segments'] = $listSelection;
    } else {
      $settings['segments_selected_by'] = 'admin';
    }

    $form = Form::createOrUpdate([
      'id' => $formId,
      'name' => $name,
      'body' => $body,
      'settings' => $settings,
      'styles' => $styles,
    ]);

    $errors = $form->getErrors();

    if (!empty($errors)) {
      return $this->badRequest($errors);
    }
    $form = Form::findOne($form->id);
    if(!$form instanceof Form) return $this->errorResponse();
    return $this->successResponse(
      $form->asArray(),
      ['is_widget' => $isWidget]
    );
  }

  public function restore($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $form = Form::findOne($id);
    if ($form instanceof Form) {
      $form->restore();
      $form = Form::findOne($form->id);
      if(!$form instanceof Form) return $this->errorResponse();
      return $this->successResponse(
        $form->asArray(),
        ['count' => 1]
      );
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This form does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function trash($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $form = Form::findOne($id);
    if ($form instanceof Form) {
      $form->trash();
      $form = Form::findOne($form->id);
      if(!$form instanceof Form) return $this->errorResponse();
      return $this->successResponse(
        $form->asArray(),
        ['count' => 1]
      );
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This form does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function delete($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $form = Form::findOne($id);
    if ($form instanceof Form) {
      $form->delete();

      return $this->successResponse(null, ['count' => 1]);
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This form does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function duplicate($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $form = Form::findOne($id);

    if ($form instanceof Form) {
      $formName = $form->name ? sprintf(__('Copy of %s', 'mailpoet'), $form->name) : '';
      $data = [
        'name' => $formName,
      ];
      $duplicate = $form->duplicate($data);
      $errors = $duplicate->getErrors();

      if (!empty($errors)) {
        return $this->errorResponse($errors);
      } else {
        $duplicate = Form::findOne($duplicate->id);
        if(!$duplicate instanceof Form) return $this->errorResponse();
        return $this->successResponse(
          $duplicate->asArray(),
          ['count' => 1]
        );
      }
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This form does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function bulkAction($data = []) {
    try {
      $meta = $this->bulkAction->apply('\MailPoet\Models\Form', $data);
      return $this->successResponse(null, $meta);
    } catch (\Exception $e) {
      return $this->errorResponse([
        $e->getCode() => $e->getMessage(),
      ]);
    }
  }
}

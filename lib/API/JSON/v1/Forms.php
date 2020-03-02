<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Config\AccessControl;
use MailPoet\Form\DisplayFormInWPContent;
use MailPoet\Form\FormFactory;
use MailPoet\Form\Renderer as FormRenderer;
use MailPoet\Form\Util;
use MailPoet\Listing;
use MailPoet\Models\Form;
use MailPoet\Models\StatisticsForms;
use MailPoet\Settings\UserFlagsController;
use MailPoet\WP\Functions as WPFunctions;

class Forms extends APIEndpoint {

  /** @var Listing\BulkActionController */
  private $bulkAction;

  /** @var Listing\Handler */
  private $listingHandler;

  /** @var Util\Styles */
  private $formStylesUtils;

  /** @var UserFlagsController */
  private $userFlags;

  /** @var FormRenderer */
  private $formRenderer;

  /** @var FormFactory */
  private $formFactory;

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_FORMS,
  ];

  public function __construct(
    Listing\BulkActionController $bulkAction,
    Listing\Handler $listingHandler,
    Util\Styles $formStylesUtils,
    UserFlagsController $userFlags,
    FormRenderer $formRenderer,
    FormFactory $formFactory
  ) {
    $this->bulkAction = $bulkAction;
    $this->listingHandler = $listingHandler;
    $this->formStylesUtils = $formStylesUtils;
    $this->userFlags = $userFlags;
    $this->formRenderer = $formRenderer;
    $this->formFactory = $formFactory;
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
    return $this->save($this->formFactory->createEmptyForm());
  }

  public function save(Form $form) {
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
    $html = $this->formRenderer->renderHTML($data);

    // convert shortcodes
    $html = WPFunctions::get()->doShortcode($html);

    // styles
    $css = $this->formStylesUtils->render($this->formRenderer->getStyles($data));

    return $this->successResponse([
      'html' => $html,
      'css' => $css,
      'form_element_styles' => $this->formRenderer->renderFormElementStyles($data),
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

    WPFunctions::get()->deleteTransient(DisplayFormInWPContent::NO_FORM_TRANSIENT_KEY);

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
    if (isset($data['editor_version']) && $data['editor_version'] === "2") {
      $this->userFlags->set('display_new_form_editor_nps_survey', true);
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

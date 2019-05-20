<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Config\AccessControl;
use MailPoet\Form\Renderer as FormRenderer;
use MailPoet\Form\Util;
use MailPoet\Listing;
use MailPoet\Models\Form;
use MailPoet\Models\StatisticsForms;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class Forms extends APIEndpoint {

  /** @var Listing\BulkActionController */
  private $bulk_action;

  /** @var Listing\Handler */
  private $listing_handler;

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_FORMS,
  ];

  function __construct(
    Listing\BulkActionController $bulk_action,
    Listing\Handler $listing_handler
  ) {
    $this->bulk_action = $bulk_action;
    $this->listing_handler = $listing_handler;
  }

  function get($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $form = Form::findOne($id);
    if ($form instanceof Form) {
      return $this->successResponse($form->asArray());
    }
    return $this->errorResponse([
      APIError::NOT_FOUND => WPFunctions::get()->__('This form does not exist.', 'mailpoet'),
    ]);
  }

  function listing($data = []) {
    $listing_data = $this->listing_handler->get('\MailPoet\Models\Form', $data);

    $data = [];
    foreach ($listing_data['items'] as $form) {
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
      'count' => $listing_data['count'],
      'filters' => $listing_data['filters'],
      'groups' => $listing_data['groups'],
    ]);
  }

  function create() {
    // create new form
    $form_data = [
      'name' => WPFunctions::get()->__('New form', 'mailpoet'),
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

    return $this->save($form_data);
  }

  function save($data = []) {
    $form = Form::createOrUpdate($data);
    $errors = $form->getErrors();

    if (empty($errors)) {
      $form = Form::findOne($form->id);
      if(!$form instanceof Form) return $this->errorResponse();
      return $this->successResponse($form->asArray());
    }
    return $this->badRequest($errors);
  }

  function previewEditor($data = []) {
    // html
    $html = FormRenderer::renderHTML($data);

    // convert shortcodes
    $html = WPFunctions::get()->doShortcode($html);

    // styles
    $css = new Util\Styles(FormRenderer::getStyles($data));

    return $this->successResponse([
      'html' => $html,
      'css' => $css->render(),
    ]);
  }

  function exportsEditor($data = []) {
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

  function saveEditor($data = []) {
    $form_id = (isset($data['id']) ? (int)$data['id'] : 0);
    $name = (isset($data['name']) ? $data['name'] : WPFunctions::get()->__('New form', 'mailpoet'));
    $body = (isset($data['body']) ? $data['body'] : []);
    $settings = (isset($data['settings']) ? $data['settings'] : []);
    $styles = (isset($data['styles']) ? $data['styles'] : '');

    // check if the form is used as a widget
    $is_widget = false;
    $widgets = WPFunctions::get()->getOption('widget_mailpoet_form');
    if (!empty($widgets)) {
      foreach ($widgets as $widget) {
        if (isset($widget['form']) && (int)$widget['form'] === $form_id) {
          $is_widget = true;
          break;
        }
      }
    }

    // check if the user gets to pick his own lists
    // or if it's selected by the admin
    $has_segment_selection = false;
    $list_selection = [];
    foreach ($body as $i => $block) {
      if ($block['type'] === 'segment') {
        $has_segment_selection = true;
        if (!empty($block['params']['values'])) {
          $list_selection = array_filter(
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
    if ($has_segment_selection === true) {
      $settings['segments_selected_by'] = 'user';
      $settings['segments'] = $list_selection;
    } else {
      $settings['segments_selected_by'] = 'admin';
    }

    $form = Form::createOrUpdate([
      'id' => $form_id,
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
      ['is_widget' => $is_widget]
    );
  }

  function restore($data = []) {
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

  function trash($data = []) {
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

  function delete($data = []) {
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

  function duplicate($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $form = Form::findOne($id);

    if ($form instanceof Form) {
      $data = [
        'name' => sprintf(__('Copy of %s', 'mailpoet'), $form->name),
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

  function bulkAction($data = []) {
    try {
      $meta = $this->bulk_action->apply('\MailPoet\Models\Form', $data);
      return $this->successResponse(null, $meta);
    } catch (\Exception $e) {
      return $this->errorResponse([
        $e->getCode() => $e->getMessage(),
      ]);
    }
  }
}

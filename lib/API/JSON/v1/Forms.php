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

  public $permissions = array(
    'global' => AccessControl::PERMISSION_MANAGE_FORMS
  );

  function __construct(
    Listing\BulkActionController $bulk_action,
    Listing\Handler $listing_handler
  ) {
    $this->bulk_action = $bulk_action;
    $this->listing_handler = $listing_handler;
  }

  function get($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $form = Form::findOne($id);
    if ($form === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => WPFunctions::get()->__('This form does not exist.', 'mailpoet')
      ));
    } else {
      return $this->successResponse($form->asArray());
    }
  }

  function listing($data = array()) {
    $listing_data = $this->listing_handler->get('\MailPoet\Models\Form', $data);

    $data = array();
    foreach ($listing_data['items'] as $form) {
      $form = $form->asArray();

      $form['signups'] = StatisticsForms::getTotalSignups($form['id']);

      $form['segments'] = (
        !empty($form['settings']['segments'])
        ? $form['settings']['segments']
        : array()
      );

      $data[] = $form;
    }

    return $this->successResponse($data, array(
      'count' => $listing_data['count'],
      'filters' => $listing_data['filters'],
      'groups' => $listing_data['groups']
    ));
  }

  function create() {
    // create new form
    $form_data = array(
      'name' => WPFunctions::get()->__('New form', 'mailpoet'),
      'body' => array(
        array(
          'id' => 'email',
          'name' => WPFunctions::get()->__('Email', 'mailpoet'),
          'type' => 'text',
          'static' => true,
          'params' => array(
            'label' => WPFunctions::get()->__('Email', 'mailpoet'),
            'required' => true
          )
        ),
        array(
          'id' => 'submit',
          'name' => WPFunctions::get()->__('Submit', 'mailpoet'),
          'type' => 'submit',
          'static' => true,
          'params' => array(
            'label' => WPFunctions::get()->__('Subscribe!', 'mailpoet')
          )
        )
      ),
      'settings' => array(
        'on_success' => 'message',
        'success_message' => WPFunctions::get()->__('Check your inbox or spam folder to confirm your subscription.', 'mailpoet'),
        'segments' => null,
        'segments_selected_by' => 'admin'
      )
    );

    return $this->save($form_data);
  }

  function save($data = array()) {
    $form = Form::createOrUpdate($data);
    $errors = $form->getErrors();

    if (!empty($errors)) {
      return $this->badRequest($errors);
    } else {
      return $this->successResponse(
        Form::findOne($form->id)->asArray()
      );
    }
  }

  function previewEditor($data = array()) {
    // html
    $html = FormRenderer::renderHTML($data);

    // convert shortcodes
    $html = WPFunctions::get()->doShortcode($html);

    // styles
    $css = new Util\Styles(FormRenderer::getStyles($data));

    return $this->successResponse(array(
      'html' => $html,
      'css' => $css->render()
    ));
  }

  function exportsEditor($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $form = Form::findOne($id);
    if ($form === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => WPFunctions::get()->__('This form does not exist.', 'mailpoet')
      ));
    } else {
      $exports = Util\Export::getAll($form->asArray());
      return $this->successResponse($exports);
    }
  }

  function saveEditor($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);

    $form_id = (isset($data['id']) ? (int)$data['id'] : 0);
    $name = (isset($data['name']) ? $data['name'] : WPFunctions::get()->__('New form', 'mailpoet'));
    $body = (isset($data['body']) ? $data['body'] : array());
    $settings = (isset($data['settings']) ? $data['settings'] : array());
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

    $form = Form::createOrUpdate(array(
      'id' => $form_id,
      'name' => $name,
      'body' => $body,
      'settings' => $settings,
      'styles' => $styles
    ));

    $errors = $form->getErrors();

    if (!empty($errors)) {
      return $this->badRequest($errors);
    } else {
      return $this->successResponse(
        Form::findOne($form->id)->asArray(),
        array('is_widget' => $is_widget)
      );
    }
  }

  function restore($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $form = Form::findOne($id);
    if ($form === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => WPFunctions::get()->__('This form does not exist.', 'mailpoet')
      ));
    } else {
      $form->restore();
      return $this->successResponse(
        Form::findOne($form->id)->asArray(),
        array('count' => 1)
      );
    }
  }

  function trash($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $form = Form::findOne($id);
    if ($form === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => WPFunctions::get()->__('This form does not exist.', 'mailpoet')
      ));
    } else {
      $form->trash();
      return $this->successResponse(
        Form::findOne($form->id)->asArray(),
        array('count' => 1)
      );
    }
  }

  function delete($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $form = Form::findOne($id);
    if ($form === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => WPFunctions::get()->__('This form does not exist.', 'mailpoet')
      ));
    } else {
      $form->delete();
      return $this->successResponse(null, array('count' => 1));
    }
  }

  function duplicate($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $form = Form::findOne($id);

    if ($form === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => WPFunctions::get()->__('This form does not exist.', 'mailpoet')
      ));
    } else {
      $data = array(
        'name' => sprintf(__('Copy of %s', 'mailpoet'), $form->name)
      );
      $duplicate = $form->duplicate($data);
      $errors = $duplicate->getErrors();

      if (!empty($errors)) {
        return $this->errorResponse($errors);
      } else {
        return $this->successResponse(
          Form::findOne($duplicate->id)->asArray(),
          array('count' => 1)
        );
      }
    }
  }

  function bulkAction($data = array()) {
    try {
      $meta = $this->bulk_action->apply('\MailPoet\Models\Form', $data);
      return $this->successResponse(null, $meta);
    } catch (\Exception $e) {
      return $this->errorResponse(array(
        $e->getCode() => $e->getMessage()
      ));
    }
  }
}

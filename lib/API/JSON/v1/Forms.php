<?php
namespace MailPoet\API\JSON\v1;
use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;

use MailPoet\Models\Form;
use MailPoet\Models\StatisticsForms;
use MailPoet\Form\Renderer as FormRenderer;
use MailPoet\Listing;
use MailPoet\Form\Util;

if(!defined('ABSPATH')) exit;

class Forms extends APIEndpoint {
  function get($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $form = Form::findOne($id);
    if($form === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This form does not exist.', 'mailpoet')
      ));
    } else {
      return $this->successResponse($form->asArray());
    }
  }

  function listing($data = array()) {
    $listing = new Listing\Handler(
      '\MailPoet\Models\Form',
      $data
    );

    $listing_data = $listing->get();

    $data = array();
    foreach($listing_data['items'] as $form) {
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
      'name' => __('New form', 'mailpoet'),
      'body' => array(
        array(
          'id' => 'email',
          'name' => __('Email', 'mailpoet'),
          'type' => 'text',
          'static' => true,
          'params' => array(
            'label' => __('Email', 'mailpoet'),
            'required' => true
          )
        ),
        array(
          'id' => 'submit',
          'name' => __('Submit', 'mailpoet'),
          'type' => 'submit',
          'static' => true,
          'params' => array(
            'label' => __('Subscribe!', 'mailpoet')
          )
        )
      ),
      'settings' => array(
        'on_success' => 'message',
        'success_message' => __('Check your inbox or spam folder to confirm your subscription.', 'mailpoet'),
        'segments' => null,
        'segments_selected_by' => 'admin'
      )
    );

    return $this->save($form_data);
  }

  function save($data = array()) {
    $form = Form::createOrUpdate($data);
    $errors = $form->getErrors();

    if(!empty($errors)) {
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
    $html = do_shortcode($html);

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
    if($form === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This form does not exist.', 'mailpoet')
      ));
    } else {
      $exports = Util\Export::getAll($form->asArray());
      return $this->successResponse($exports);
    }
  }

  function saveEditor($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);

    $form_id = (isset($data['id']) ? (int)$data['id'] : 0);
    $name = (isset($data['name']) ? $data['name'] : __('New form', 'mailpoet'));
    $body = (isset($data['body']) ? $data['body'] : array());
    $settings = (isset($data['settings']) ? $data['settings'] : array());
    $styles = (isset($data['styles']) ? $data['styles'] : '');

    // check if the form is used as a widget
    $is_widget = false;
    $widgets = get_option('widget_mailpoet_form');
    if(!empty($widgets)) {
      foreach($widgets as $widget) {
        if(isset($widget['form']) && (int)$widget['form'] === $form_id) {
          $is_widget = true;
          break;
        }
      }
    }

    // check if the user gets to pick his own lists
    // or if it's selected by the admin
    $has_segment_selection = false;
    foreach($body as $i => $block) {
      if($block['type'] === 'segment') {
        $has_segment_selection = true;
        if(!empty($block['params']['values'])) {
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
    if($has_segment_selection === true) {
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

    if(!empty($errors)) {
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
    if($form === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This form does not exist.', 'mailpoet')
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
    if($form === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This form does not exist.', 'mailpoet')
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
    if($form === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This form does not exist.', 'mailpoet')
      ));
    } else {
      $form->delete();
      return $this->successResponse(null, array('count' => 1));
    }
  }

  function duplicate($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $form = Form::findOne($id);

    if($form === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This form does not exist.', 'mailpoet')
      ));
    } else {
      $data = array(
        'name' => sprintf(__('Copy of %s', 'mailpoet'), $form->name)
      );
      $duplicate = $form->duplicate($data);
      $errors = $duplicate->getErrors();

      if(!empty($errors)) {
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
      $bulk_action = new Listing\BulkAction(
        '\MailPoet\Models\Form',
        $data
      );
      $meta = $bulk_action->apply();
      return $this->successResponse(null, $meta);
    } catch(\Exception $e) {
      return $this->errorResponse(array(
        $e->getCode() => $e->getMessage()
      ));
    }
  }
}

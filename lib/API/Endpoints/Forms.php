<?php
namespace MailPoet\API\Endpoints;

use \MailPoet\Models\Form;
use \MailPoet\Models\StatisticsForms;
use \MailPoet\Form\Renderer as FormRenderer;
use \MailPoet\Listing;
use \MailPoet\Form\Util;

if(!defined('ABSPATH')) exit;

class Forms {
  function __construct() {
  }

  function get($id = false) {
    $form = Form::findOne($id);
    if($form !== false) {
      $form = $form->asArray();
    }
    return $form;
  }

  function listing($data = array()) {
    $listing = new Listing\Handler(
      '\MailPoet\Models\Form',
      $data
    );

    $listing_data = $listing->get();

    // fetch segments relations for each returned item
    foreach($listing_data['items'] as $key => $form) {
      $form = $form->asArray();

      $form['signups'] = StatisticsForms::getTotalSignups($form['id']);

      $form['segments'] = (
        !empty($form['settings']['segments'])
        ? $form['settings']['segments']
        : array()
      );
      $listing_data['items'][$key] = $form;
    }

    return $listing_data;
  }

  function create() {
    // create new form
    $form_data = array(
      'name' => __('New form'),
      'body' => array(
        array(
          'id' => 'email',
          'name' => __('Email'),
          'type' => 'text',
          'static' => true,
          'params' => array(
            'label' => __('Email'),
            'required' => true
          )
        ),
        array(
          'id' => 'submit',
          'name' => __('Submit'),
          'type' => 'submit',
          'static' => true,
          'params' => array(
            'label' => __('Subscribe!')
          )
        )
      ),
      'settings' => array(
        'on_success' => 'message',
        'success_message' => __('Check your inbox or spam folder to confirm your subscription.'),
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
      return array(
        'result' => false,
        'errors' => $errors
      );
    } else {
      return array(
        'result' => true,
        'form_id' => $form->id()
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

    return array(
      'html' => $html,
      'css' => $css->render()
    );
  }

  function exportsEditor($id) {
    $exports = false;

    $form = Form::findOne($id);

    if($form !== false) {
      $exports = Util\Export::getAll($form->asArray());
    }

    return $exports;
  }

  function saveEditor($data = array()) {
    $form_id = (isset($data['id']) ? (int)$data['id'] : 0);
    $name = (isset($data['name']) ? $data['name'] : __('New form'));
    $body = (isset($data['body']) ? $data['body'] : array());
    $settings = (isset($data['settings']) ? $data['settings'] : array());
    $styles = (isset($data['styles']) ? $data['styles'] : '');

    if(empty($body) || empty($settings)) {
      // error
      return false;
    } else {
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
                  ? (int)$segment['id']
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
      } else {
        $settings['segments_selected_by'] = 'admin';
      }
    }

    $form = Form::createOrUpdate(array(
      'id' => $form_id,
      'name' => $name,
      'body' => $body,
      'settings' => $settings,
      'styles' => $styles
    ));

    if($form->getErrors() === false) {
      return array(
        'result' => true,
        'is_widget' => $is_widget
      );
    } else {
      return array(
        'result' => false,
        'errors' => $form->getErrors()
      );
    }
  }

  function restore($id) {
    $form = Form::findOne($id);
    if($form !== false) {
      $form->restore();
    }
    return ($form->getErrors() === false);
  }

  function trash($id) {
    $form = Form::findOne($id);
    if($form !== false) {
      $form->trash();
    }
    return ($form->getErrors() === false);
  }

  function delete($id) {
    $form = Form::findOne($id);
    if($form !== false) {
      $form->delete();
      return 1;
    }
    return false;
  }

  function duplicate($id) {
    $result = false;

    $form = Form::findOne($id);
    if($form !== false) {
      $data = array(
        'name' => sprintf(__('Copy of %s'), $form->name)
      );
      $result = $form->duplicate($data)->asArray();
    }

    return $result;
  }

  function bulkAction($data = array()) {
    $bulk_action = new Listing\BulkAction(
      '\MailPoet\Models\Form',
      $data
    );

    return $bulk_action->apply();
  }
}

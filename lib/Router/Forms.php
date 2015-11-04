<?php
namespace MailPoet\Router;
use \MailPoet\Models\Form;
use \MailPoet\Form\Renderer as FormRenderer;
use \MailPoet\Listing;
use \MailPoet\Form\Util;

if(!defined('ABSPATH')) exit;

class Forms {
  function __construct() {
  }

  function get($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : 0);

    $form = Form::findOne($id);
    if($form === false) {
      wp_send_json(false);
    } else {
      $form = $form->asArray();
      wp_send_json($form);
    }
  }

  function listing($data = array()) {
    $listing = new Listing\Handler(
      '\MailPoet\Models\Form',
      $data
    );

    $listing_data = $listing->get();

    // fetch segments relations for each returned item
    foreach($listing_data['items'] as &$item) {
      // form's segments
      $form_settings = unserialize($item['settings']);

      $item['segments'] = (
        !empty($form_settings['segments'])
        ? $form_settings['segments']
        : array()
      );
    }

    wp_send_json($listing_data);
  }

  function getAll() {
    $collection = Form::findArray();
    wp_send_json($collection);
  }

  function create() {
    // create new form
    $form_data = array(
      'name' => __('New form'),
      'body' => array(
        array(
          'name' => __('Email'),
          'type' => 'input',
          'field' => 'email',
          'static' => true,
          'params' => array(
            'label' => __('Email'),
            'required' => true
          )
        ),
        array(
          'name' => __('Submit'),
          'type' => 'submit',
          'field' => 'submit',
          'static' => true,
          'params' => array(
            'label' => __('Subscribe!')
          )
        )
      ),
      'settings' => array(
        'on_success' => 'message',
        'success_message' => __('Check your inbox or spam folder now to confirm your subscription.'),
        'segments' => null,
        'segments_selected_by' => 'admin'
      )
    );

    $form = Form::createOrUpdate($form_data);

    if($form !== false && $form->id()) {
      wp_send_json(
        admin_url('admin.php?page=mailpoet-form-editor&id='.$form->id())
      );
    } else {
      wp_send_json(false);
    }
  }

  function save($data = array()) {
    $form = Form::createOrUpdate($data);

    if($form !== false && $form->id()) {
      wp_send_json($form->id());
    } else {
      wp_send_json($form);
    }
  }

  function preview($id) {
    $form = Form::findOne($id);
    if($form === false) {
      wp_send_json(false);
    } else {
      $form = $form->asArray();

      // html
      $html = FormRenderer::renderHTML($form);

      // convert shortcodes
      $html = do_shortcode($html);

      // styles
      $css = new Util\Styles(FormRenderer::getStyles($form));

      wp_send_json(array(
        'html' => $html,
        'css' => $css->render()
      ));
    }
  }

  function save_editor($data = array()) {
    $form_id = (isset($data['id']) ? (int)$data['id'] : 0);
    $body = (isset($data['body']) ? $data['body'] : array());
    $settings = (isset($data['settings']) ? $data['settings'] : array());
    $styles = (isset($data['styles']) ? $data['styles'] : array());

    if(empty($body) || empty($settings)) {
      // error
      wp_send_json(false);
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
    }

    $form = Form::createOrUpdate(array(
      'id' => $form_id,
      'body' => $body,
      'settings' => $settings,
      'styles' => $styles
    ));

    // response
    wp_send_json(array(
      'result' => ($form !== false),
      'is_widget' => $is_widget
    ));
}

  function restore($id) {
    $result = false;

    $form = Form::findOne($id);
    if($form !== false) {
      $result = $form->restore();
    }

    wp_send_json($result);
  }

  function trash($id) {
    $result = false;

    $form = Form::findOne($id);
    if($form !== false) {
      $result = $form->trash();
    }

    wp_send_json($result);
  }

  function delete($id) {
    $result = false;

    $form = Form::findOne($id);
    if($form !== false) {
      $form->delete();
      $result = 1;
    }

    wp_send_json($result);
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

    wp_send_json($result);
  }

  function item_action($data = array()) {
    $item_action = new Listing\ItemAction(
      '\MailPoet\Models\Form',
      $data
    );

    wp_send_json($item_action->apply());
  }

  function bulk_action($data = array()) {
    $bulk_action = new Listing\BulkAction(
      '\MailPoet\Models\Form',
      $data
    );

    wp_send_json($bulk_action->apply());
  }
}

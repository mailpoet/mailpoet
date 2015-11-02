<?php
namespace MailPoet\Router;
use \MailPoet\Models\Form;
use \MailPoet\Models\FormSegment;
use \MailPoet\Listing;

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
      $segments = $form->segments();
      $form = $form->asArray();
      $form['segments'] = array_map(function($segment) {
        return $segment['id'];
      }, $segments->findArray());

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
      $relations = FormSegment::select('segment_id')
        ->where('form_id', $item['id'])
        ->findMany();
      $item['segments'] = array_map(function($relation) {
        return $relation->segment_id;
      }, $relations);
    }

    wp_send_json($listing_data);
  }

  function getAll() {
    $collection = Form::findArray();
    wp_send_json($collection);
  }

  function save($data = array()) {
    if(isset($data['segments'])) {
      $segment_ids = $data['segments'];
      unset($data['segments']);
    }

    $form = Form::createOrUpdate($data);

    if($form->id() && !empty($segment_ids)) {
      // remove previous relationships with segments
      FormSegment::where('form_id', $form->id())->deleteMany();

      // create relationship with segments
      foreach($segment_ids as $segment_id) {
        $relation = FormSegment::create();
        $relation->segment_id = $segment_id;
        $relation->form_id = $form->id();
        $relation->save();
      }
    }

    if($form !== false && $form->id()) {
      wp_send_json($form->id());
    } else {
      wp_send_json($form);
    }
  }

  function save_editor($data = array()) {
    $form_id = (isset($data['id']) ? (int)$data['id'] : 0);
    $form_data = (isset($data['form']) ? $data['form'] : array());

    if(empty($form_data)) {
      // error
      wp_send_json(false);
    } else {
      // check if the form is displayed as a widget (we'll display a different "saved!" message in this case)
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

      // check if the user gets to pick his own lists or if it's selected by the admin
      $has_list_selection = false;


      $blocks = (isset($form_data['body']) ? $form_data['body'] : array());
      if(!empty($blocks)) {
        foreach ($blocks as $i => $block) {
          if($block['type'] === 'list') {
            $has_list_selection = true;
            if(!empty($block['params']['values'])) {
              $list_selection = array_map(function($segment) {
                return (int)$segment['id'];
              }, $block['params']['values']);
            }
            break;
          }
        }
      }

      // check list selectio
      if($has_list_selection === true) {
        $form_data['lists_selected_by'] = 'user';
      } else {
        $form_data['lists_selected_by'] = 'admin';
      }
    }

    $form = Form::createOrUpdate(array(
      'id' => $form_id,
      'data' => $form_data
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

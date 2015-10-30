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

    if($form === false) {
      wp_send_json($form->getValidationErrors());
    } else {
      wp_send_json(true);
    }
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

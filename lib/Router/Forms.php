<?php
namespace MailPoet\Router;
use \MailPoet\Models\Form;
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
      wp_send_json($form->asArray());
    }
  }

  function listing($data = array()) {
    $listing = new Listing\Handler(
      '\MailPoet\Models\Form',
      $data
    );

    $listing_data = $listing->get();

    wp_send_json($listing_data);
  }

  function getAll() {
    $collection = Form::findArray();
    wp_send_json($collection);
  }

  function save($data = array()) {
    $result = Form::createOrUpdate($data);

    if($result !== true) {
      wp_send_json($result);
    } else {
      wp_send_json(true);
    }
  }

  function restore($id) {
    $form = Form::findOne($id);
    if($form !== false) {
      $form->set_expr('deleted_at', 'NULL');
      $result = $form->save();
    } else {
      $result = false;
    }
    wp_send_json($result);
  }

  function delete($data = array()) {
    $form = Form::findOne($data['id']);
    $confirm_delete = filter_var($data['confirm'], FILTER_VALIDATE_BOOLEAN);
    if($form !== false) {
      if($confirm_delete) {
        $form->delete();
        $result = true;
      } else {
        $form->set_expr('deleted_at', 'NOW()');
        $result = $form->save();
      }
    } else {
      $result = false;
    }
    wp_send_json($result);
  }

  function duplicate($id) {
    $result = false;

    $form = Form::duplicate($id);
    if($form !== false) {
      $result = $form;
    }
    wp_send_json($result);
  }

  function bulk_action($data = array()) {
    $bulk_action = new Listing\BulkAction(
      '\MailPoet\Models\Form',
      $data
    );

    wp_send_json($bulk_action->apply());
  }
}

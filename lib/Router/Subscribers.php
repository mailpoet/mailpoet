<?php
namespace MailPoet\Router;

use MailPoet\Listing;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\Segment;
use MailPoet\Models\Setting;
use MailPoet\Models\Form;
use MailPoet\Models\StatisticsForms;
use MailPoet\Util\Url;

if(!defined('ABSPATH')) exit;

class Subscribers {
  function __construct() {
  }

  function get($id = null) {
    $subscriber = Subscriber::findOne($id);
    if($subscriber !== false) {
      $subscriber = $subscriber
        ->withCustomFields()
        ->withSubscriptions()
        ->asArray();
    }
    return $subscriber;
  }

  function listing($data = array()) {
    $listing = new Listing\Handler(
      '\MailPoet\Models\Subscriber',
      $data
    );

    $listing_data = $listing->get();

    // fetch segments relations for each returned item
    foreach($listing_data['items'] as $key => $subscriber) {
      $listing_data['items'][$key] = $subscriber
        ->withSubscriptions()
        ->asArray();
    }

    return $listing_data;
  }

  function save($data = array()) {
    $subscriber = Subscriber::createOrUpdate($data);
    $errors = $subscriber->getErrors();

    if(!empty($errors)) {
      return array(
        'result' => false,
        'errors' => $errors
      );
    } else {
      return array(
        'result' => true
      );
    }
  }

  function subscribe($data = array()) {
    $doing_ajax = (bool)(defined('DOING_AJAX') && DOING_AJAX);
    $errors = array();

    $form = Form::findOne($data['form_id']);
    unset($data['form_id']);
    if($form === false || !$form->id()) {
      $errors[] = __('This form does not exist.');
    }

    $segment_ids = (!empty($data['segments'])
      ? (array)$data['segments']
      : array()
    );
    unset($data['segments']);

    if(empty($segment_ids)) {
      $errors[] = __('Please select a list');
    }

    if(!empty($errors)) {
      return array(
        'result' => false,
        'errors' => $errors
      );
    }

    $subscriber = Subscriber::subscribe($data, $segment_ids);
    $errors = $subscriber->getErrors();
    $result = ($errors === false && $subscriber->id() > 0);

    // record form statistics
    if($result === true && $form !== false && $form->id > 0) {
      StatisticsForms::record($form->id, $subscriber->id);
    }

    // get success message to display after subscription
    $form_settings = (
      isset($form->settings)
      ? unserialize($form->settings)
      : null
    );

    if($form_settings !== null) {
      switch($form_settings['on_success']) {
        case 'page':
          $success_page_url = get_permalink($form_settings['success_page']);

          // response depending on context
          if($doing_ajax === true) {
            return array(
              'result' => $result,
              'page' => $success_page_url,
              'errors' => $errors
            );
          } else {
            if($result === false) {
              Url::redirectBack();
            } else {
              Url::redirectTo($success_page_url);
            }
          }
        break;

        case 'message':
        default:
          // response depending on context
          if($doing_ajax === true) {
            return array(
              'result' => $result,
              'errors' => $errors
            );
          } else {
            $params = (
              ($result === true)
              ? array('mailpoet_success' => $form->id)
              : array()
            );

            Url::redirectBack($params);
          }
        break;
      }
    }
  }

  function restore($id) {
    $subscriber = Subscriber::findOne($id);
    if($subscriber !== false) {
      $subscriber->restore();
    }
    return ($subscriber->getErrors() === false);
  }

  function trash($id) {
    $subscriber = Subscriber::findOne($id);
    if($subscriber !== false) {
      $subscriber->trash();
    }
    return ($subscriber->getErrors() === false);
  }

  function delete($id) {
    $subscriber = Subscriber::findOne($id);
    if($subscriber !== false) {
      $subscriber->delete();
      return 1;
    }
    return false;
  }

  function bulkAction($data = array()) {
    $bulk_action = new Listing\BulkAction(
      '\MailPoet\Models\Subscriber',
      $data
    );

    return $bulk_action->apply();
  }
}

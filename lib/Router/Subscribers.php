<?php
namespace MailPoet\Router;

use MailPoet\Listing;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\Segment;
use MailPoet\Models\Setting;
use MailPoet\Models\Form;

if(!defined('ABSPATH')) exit;

class Subscribers {
  function __construct() {
  }

  function get($id = false) {
    $subscriber = Subscriber::findOne($id);
    if($subscriber !== false && $subscriber->id() > 0) {
      $segments = $subscriber->segments()->findArray();

      $subscriber = $subscriber->withCustomFields()->asArray();
      $subscriber['segments'] = array_map(function($segment) {
        return $segment['id'];
      }, $segments);
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
    foreach($listing_data['items'] as &$item) {
      // avatar
      $item['avatar_url'] = get_avatar_url($item['email'], array(
        'size' => 32
      ));

      // subscriber's segments
      $relations = SubscriberSegment::select('segment_id')
        ->where('subscriber_id', $item['id'])
        ->findMany();
      $item['segments'] = array_map(function($relation) {
        return $relation->segment_id;
      }, $relations);
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
      $errors[] = __('You need to select a list');
    }

    if(!empty($errors)) {
      return array(
        'result' => false,
        'errors' => $errors
      );
    }

    $subscriber = Subscriber::subscribe($data, $segment_ids);

    $result = false;
    if($subscriber === false || !$subscriber->id()) {
      $errors = array_merge($errors, $subscriber->getValidationErrors());
    } else {
      $result = true;
    }

    if(!empty($errors)) {
      return array(
        'result' => false,
        'errors' => $errors
      );
    }

    // get success message to display after subscription
    $form_settings = (
      isset($form->settings)
      ? unserialize($form->settings) : null
    );

    if($form_settings !== null) {
      $message = $form_settings['success_message'];

      // url params for non ajax requests
      if($doing_ajax === false) {
        // get referer
        $referer = (wp_get_referer() !== false)
          ? wp_get_referer() : $_SERVER['HTTP_REFERER'];

        // redirection parameters
        $params = array(
          'mailpoet_form' => $form->id()
        );

        // handle success/error messages
        if($result === false) {
          $params['mailpoet_error'] = urlencode($message);
        } else {
          $params['mailpoet_success'] = urlencode($message);
        }
      }

      switch($form_settings['on_success']) {
        case 'page':
          // response depending on context
          if($doing_ajax === true) {
            return array(
              'result' => $result,
              'page' => get_permalink($form_settings['success_page']),
              'message' => $message
            );
          } else {
            $redirect_to = ($result === false) ? $referer : get_permalink($form_settings['success_page']);
            wp_redirect(add_query_arg($params, $redirect_to));
          }
        break;

        case 'message':
        default:
          // response depending on context
          if($doing_ajax === true) {
            return array(
              'result' => true,
              'message' => $message
            );
          } else {
            // redirect to previous page
            wp_redirect(add_query_arg($params, $referer));
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

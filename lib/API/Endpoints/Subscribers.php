<?php
namespace MailPoet\API\Endpoints;
use \MailPoet\API\Endpoint as APIEndpoint;
use \MailPoet\API\Error as APIError;

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

class Subscribers extends APIEndpoint {
  function get($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $subscriber = Subscriber::findOne($id);
    if($subscriber === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This subscriber does not exist.')
      ));
    } else {
      return $this->successResponse(
        $subscriber
          ->withCustomFields()
          ->withSubscriptions()
          ->asArray()
      );
    }
  }

  function listing($data = array()) {
    $listing = new Listing\Handler(
      '\MailPoet\Models\Subscriber',
      $data
    );

    $listing_data = $listing->get();

    $data = array();
    foreach($listing_data['items'] as $subscriber) {
      $data[] = $subscriber
        ->withSubscriptions()
        ->asArray();
    }

    return $this->successResponse($data, array(
      'count' => $listing_data['count'],
      'filters' => $listing_data['filters'],
      'groups' => $listing_data['groups']
    ));
  }

  function subscribe($data = array()) {
    $doing_ajax = (bool)(defined('DOING_AJAX') && DOING_AJAX);
    $errors = array();

    $form = Form::findOne($data['form_id']);
    unset($data['form_id']);
    if($form === false || !$form->id()) {
      $errors[] = __('This form does not exist');
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

  function save($data = array()) {
    $subscriber = Subscriber::createOrUpdate($data);
    $errors = $subscriber->getErrors();

    if(!empty($errors)) {
      return $this->badRequest($errors);
    } else {
      return $this->successResponse(
        Subscriber::findOne($subscriber->id)->asArray()
      );
    }
  }

  function restore($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $subscriber = Subscriber::findOne($id);
    if($subscriber === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This subscriber does not exist.')
      ));
    } else {
      $subscriber->restore();
      return $this->successResponse(
        Subscriber::findOne($subscriber->id)->asArray(),
        array('count' => 1)
      );
    }
  }

  function trash($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $subscriber = Subscriber::findOne($id);
    if($subscriber === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This subscriber does not exist.')
      ));
    } else {
      $subscriber->trash();
      return $this->successResponse(
        Subscriber::findOne($subscriber->id)->asArray(),
        array('count' => 1)
      );
    }
  }

  function delete($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $subscriber = Subscriber::findOne($id);
    if($subscriber === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This subscriber does not exist.')
      ));
    } else {
      $subscriber->delete();
      return $this->successResponse(null, array('count' => 1));
    }
  }

  function bulkAction($data = array()) {
    try {
      $bulk_action = new Listing\BulkAction(
        '\MailPoet\Models\Subscriber',
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

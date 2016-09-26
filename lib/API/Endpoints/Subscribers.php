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

if(!defined('ABSPATH')) exit;

class Subscribers extends APIEndpoint {
  function get($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $subscriber = Subscriber::findOne($id);
    if($subscriber === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This subscriber does not exist.', Env::$plugin_name)
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
    $form_id = (isset($data['form_id']) ? (int)$data['form_id'] : false);
    $form = Form::findOne($form_id);
    unset($data['form_id']);

    $segment_ids = (!empty($data['segments'])
      ? (array)$data['segments']
      : array()
    );
    unset($data['segments']);

    if(empty($segment_ids)) {
      return $this->badRequest(array(
        APIError::BAD_REQUEST => __('Please select a list', Env::$plugin_name)
      ));
    }

    $subscriber = Subscriber::subscribe($data, $segment_ids);
    $errors = $subscriber->getErrors();

    if($errors !== false) {
      return $this->badRequest($errors);
    } else {
      $meta = array();

      if($form !== false) {
        // record form statistics
        StatisticsForms::record($form->id, $subscriber->id);

        $form = $form->asArray();

        if($form['settings']['on_success'] === 'page') {
          // redirect to a page on a success, pass the page url in the meta
          $meta['redirect_url'] = get_permalink($form['settings']['success_page']);
        } else if($form['settings']['on_success'] === 'url') {
          $meta['redirect_url'] = $form['settings']['success_url'];
        }
      }

      return $this->successResponse(
        Subscriber::findOne($subscriber->id)->asArray(),
        $meta
      );
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
        APIError::NOT_FOUND => __('This subscriber does not exist.', Env::$plugin_name)
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
        APIError::NOT_FOUND => __('This subscriber does not exist.', Env::$plugin_name)
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
        APIError::NOT_FOUND => __('This subscriber does not exist.', Env::$plugin_name)
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

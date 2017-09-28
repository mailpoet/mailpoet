<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Config\AccessControl;
use MailPoet\Listing;
use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\Models\Form;
use MailPoet\Models\StatisticsForms;
use MailPoet\Models\Subscriber;
use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class Subscribers extends APIEndpoint {
  const SUBSCRIPTION_LIMIT_COOLDOWN = 60;

  public $permissions = array(
    'global' => AccessControl::PERMISSION_MANAGE_SUBSCRIBERS,
    'methods' => array('subscribe' => AccessControl::NO_ACCESS_RESTRICTION)
  );

  function get($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $subscriber = Subscriber::findOne($id);
    if($subscriber === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This subscriber does not exist.', 'mailpoet')
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

    if(!$form) {
      return $this->badRequest(array(
        APIError::BAD_REQUEST => __('Please specify a valid form ID.', 'mailpoet')
      ));
    }
    if(!empty($data['email'])) {
      return $this->badRequest(array(
        APIError::BAD_REQUEST => __('Please leave the first field empty.', 'mailpoet')
      ));
    }

    $data = $this->deobfuscateFormPayload($data);

    $segment_ids = (!empty($data['segments'])
      ? (array)$data['segments']
      : array()
    );
    $segment_ids = $form->filterSegments($segment_ids);
    unset($data['segments']);


    if(empty($segment_ids)) {
      return $this->badRequest(array(
        APIError::BAD_REQUEST => __('Please select a list.', 'mailpoet')
      ));
    }

    // only accept fields defined in the form
    $form_fields = $form->getFieldList();
    $data = array_intersect_key($data, array_flip($form_fields));

    // make sure we don't allow too many subscriptions with the same ip address
    $subscription_count = Subscriber::where(
        'subscribed_ip',
        Helpers::getIP()
      )->whereRaw(
        '(TIME_TO_SEC(TIMEDIFF(NOW(), created_at)) < ? OR TIME_TO_SEC(TIMEDIFF(NOW(), updated_at)) < ?)',
        array(self::SUBSCRIPTION_LIMIT_COOLDOWN, self::SUBSCRIPTION_LIMIT_COOLDOWN)
      )->count();

    if($subscription_count > 0) {
      throw new \Exception(__('You need to wait before subscribing again.', 'mailpoet'));
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

        if(!empty($form['settings']['on_success'])) {
          if($form['settings']['on_success'] === 'page') {
            // redirect to a page on a success, pass the page url in the meta
            $meta['redirect_url'] = get_permalink($form['settings']['success_page']);
          } else if($form['settings']['on_success'] === 'url') {
            $meta['redirect_url'] = $form['settings']['success_url'];
          }
        }
      }

      return $this->successResponse(
        Subscriber::findOne($subscriber->id)->asArray(),
        $meta
      );
    }
  }

  private function deobfuscateFormPayload($data) {
    $obfuscator = new FieldNameObfuscator();
    return $obfuscator->deobfuscateFormPayload($data);
  }

  function save($data = array()) {
    if(empty($data['segments'])) {
      $data['segments'] = array();
    }
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
        APIError::NOT_FOUND => __('This subscriber does not exist.', 'mailpoet')
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
        APIError::NOT_FOUND => __('This subscriber does not exist.', 'mailpoet')
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
        APIError::NOT_FOUND => __('This subscriber does not exist.', 'mailpoet')
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

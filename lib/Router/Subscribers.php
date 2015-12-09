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

  function get($data = array()) {
    $id = (isset($data['id']) ? (int) $data['id'] : 0);

    $subscriber = Subscriber::findOne($id);
    if($subscriber === false) {
      wp_send_json(false);
    } else {
      $segments = $subscriber->segments()->findArray();

      $subscriber = $subscriber->asArray();
      $subscriber['segments'] = array_map(function($segment) {
        return $segment['id'];
      }, $segments);

      wp_send_json($subscriber);
    }
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

    wp_send_json($listing_data);
  }

  function getAll() {
    $collection = Subscriber::findArray();
    wp_send_json($collection);
  }

  function save($data = array()) {
    $errors = array();
    $result = false;
    $segments = false;

    if(array_key_exists('segments', $data)) {
      $segments = $data['segments'];
      unset($data['segments']);
    }

    $subscriber = Subscriber::createOrUpdate($data);

    if($subscriber !== false && !$subscriber->id()) {
      $errors = $subscriber->getValidationErrors();
    } else {
      $result = true;

      if($segments !== false) {
        SubscriberSegment::where('subscriber_id', $subscriber->id)
          ->deleteMany();

        if(!empty($segments)) {
          foreach($segments as $segment_id) {
            $relation = SubscriberSegment::create();
            $relation->segment_id = $segment_id;
            $relation->subscriber_id = $subscriber->id;
            $relation->save();
          }
        }
      }
    }
    wp_send_json(array(
      'result' => $result,
      'errors' => $errors
    ));
  }

  function subscribe($data = array()) {
    $doing_ajax = (bool)(defined('DOING_AJAX') && DOING_AJAX);
    $errors = array();

    $form = Form::findOne($data['form_id']);
    unset($data['form_id']);
    if($form === false || !$form->id()) {
      $errors[] = __('This form does not exist.');
    }

    if(empty($data['segments'])) {
      $errors[] = __('You need to select a list');
    } else {
      $segments = Segment::whereIn('id', (array)$data['segments'])->findMany();

      if(empty($segments)) {
        $errors[] = __('You need to select a list');
      }
    }
    unset($data['segments']);

    $subscriber = false;
    if(!empty($errors)) {
      wp_send_json(array('errors' => $errors));
    } else {
      if(!empty($data['email'])) {
        $subscriber = Subscriber::where('email', $data['email'])->findOne();
      }
    }

    $signup_confirmation = Setting::getValue('signup_confirmation', array());

    if($subscriber === false) {
      // create new subscriber
      $data['status'] = (
        (!empty($signup_confirmation['enabled']))
        ? 'unconfirmed' : 'subscribed'
      );

      // custom fields
      $custom_fields = array();
      foreach($data as $key => $value) {
        if(strpos($key, 'cf_') === 0) {
          $custom_fields[substr($key, 3)] = $value;
          unset($data[$key]);
        }
      }

      // insert new subscriber
      $subscriber = Subscriber::createOrUpdate($data);

      if($subscriber === false || !$subscriber->id()) {
        $errors = array_merge($errors, $subscriber->getValidationErrors());
      } else {
        // add custom fields
        if(!empty($custom_fields)) {
          foreach($custom_fields as $custom_field_id => $value) {
            if(is_array($value)) {
              // date
              $value = mktime(0, 0, 0, $value['month'], $value['day'], $value['year']);
            }
            $subscriber_custom_field = SubscriberCustomField::create();
            $subscriber_custom_field->hydrate(array(
              'subscriber_id' => $subscriber->id(),
              'custom_field_id' => $custom_field_id,
              'value' => $value
            ));
            $subscriber_custom_field->save();
          }
        }
      }
    } else {
      $subscriber->set('status', (
        !empty($signup_confirmation['enabled'])
        ? 'unconfirmed' : 'subscribed'
      ));

      // restore deleted subscriber
      if($subscriber->deleted_at !== NULL) {
        $subscriber->setExpr('deleted_at', 'NULL');
      }

      if(!$subscriber->save()) {
        $errors[] = __('An error occurred. Please try again later.');
      }
    }

    // get segments
    // IDEA: $subscriptions->addToSegments($data['segments']);
    $segments_subscribed = array();
    foreach($segments as $segment) {
      if($segment->addSubscriber($subscriber->id())) {
        $segments_subscribed[] = $segment->id;
      }
    }

    // if signup confirmation is enabled and the subscriber is unconfirmed
    if(!empty($signup_confirmation['enabled'])
      && !empty($segments_subscribed)
      && $subscriber->status !== 'subscribed'
    ) {
      // TODO: send confirmation email
      // resend confirmation email
      $is_sent = true;
      /*$is_sent = static::sendSignupConfirmation(
        $subscriber->asArray(),
        $segments->asArray()
      );*/

      // error message if the email could not be sent
      if($is_sent === false) {
        $errors[] = __('The signup confirmation email could not be sent. Please check your settings.');
      }
    }

    // get success message to display after subscription
    $form_settings = (
      isset($form->settings)
      ? unserialize($form->settings) : null
    );

    if(!empty($errors)) {
      wp_send_json(array(
        'result' => false,
        'errors' => $errors
      ));
    } else {
      $result = true;
    }

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

      switch ($form_settings['on_success']) {
        case 'page':
          // response depending on context
          if($doing_ajax === true) {
            wp_send_json(array(
              'result' => $result,
              'page' => get_permalink($form_settings['success_page']),
              'message' => $message
            ));
          } else {
            $redirect_to = ($result === false) ? $referer : get_permalink($form_settings['success_page']);
            wp_redirect(add_query_arg($params, $redirect_to));
          }
        break;

        case 'message':
        default:
          // response depending on context
          if($doing_ajax === true) {
            wp_send_json(array(
              'result' => $result,
              'message' => $message
            ));
          } else {
            // redirect to previous page
            wp_redirect(add_query_arg($params, $referer));
          }
        break;
      }
    }
  }

  function restore($id) {
    $result = false;

    $subscriber = Subscriber::findOne($id);
    if($subscriber !== false) {
      $result = $subscriber->restore();
    }

    wp_send_json($result);
  }

  function trash($id) {
    $result = false;

    $subscriber = Subscriber::findOne($id);
    if($subscriber !== false) {
      $result = $subscriber->trash();
    }

    wp_send_json($result);
  }

  function delete($id) {
    $result = false;

    $subscriber = Subscriber::findOne($id);
    if($subscriber !== false) {
      $subscriber->delete();
      $result = 1;
    }

    wp_send_json($result);
  }

  function bulkAction($data = array()) {
    $bulk_action = new Listing\BulkAction(
      '\MailPoet\Models\Subscriber',
      $data
    );

    wp_send_json($bulk_action->apply());
  }
}

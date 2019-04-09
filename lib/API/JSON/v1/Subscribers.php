<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Config\AccessControl;
use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\Listing;
use MailPoet\Models\Form;
use MailPoet\Models\StatisticsForms;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\Segments\BulkAction;
use MailPoet\Segments\SubscribersListings;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\RequiredCustomFieldValidator;
use MailPoet\Subscribers\Source;
use MailPoet\Subscribers\SubscriberActions;
use MailPoet\Subscription\Throttling as SubscriptionThrottling;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class Subscribers extends APIEndpoint {
  const SUBSCRIPTION_LIMIT_COOLDOWN = 60;

  public $permissions = array(
    'global' => AccessControl::PERMISSION_MANAGE_SUBSCRIBERS,
    'methods' => array('subscribe' => AccessControl::NO_ACCESS_RESTRICTION)
  );

  /** @var Listing\BulkActionController */
  private $bulk_action_controller;

  /** @var SubscribersListings */
  private $subscribers_listings;

  /** @var SubscriberActions */
  private $subscriber_actions;

  /** @var RequiredCustomFieldValidator */
  private $required_custom_field_validator;

  /** @var Listing\Handler */
  private $listing_handler;

  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  public function __construct(
    Listing\BulkActionController $bulk_action_controller,
    SubscribersListings $subscribers_listings,
    SubscriberActions $subscriber_actions,
    RequiredCustomFieldValidator $required_custom_field_validator,
    Listing\Handler $listing_handler,
    WPFunctions $wp,
    SettingsController $settings
  ) {
    $this->bulk_action_controller = $bulk_action_controller;
    $this->subscribers_listings = $subscribers_listings;
    $this->subscriber_actions = $subscriber_actions;
    $this->required_custom_field_validator = $required_custom_field_validator;
    $this->listing_handler = $listing_handler;
    $this->wp = $wp;
    $this->settings = $settings;
  }

  function get($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $subscriber = Subscriber::findOne($id);
    if ($subscriber === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => WPFunctions::get()->__('This subscriber does not exist.', 'mailpoet')
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

    if (!isset($data['filter']['segment'])) {
      $listing_data = $this->listing_handler->get('\MailPoet\Models\Subscriber', $data);
    } else {
      $listing_data = $this->subscribers_listings->getListingsInSegment($data);
    }

    $data = array();
    foreach ($listing_data['items'] as $subscriber) {
      $data[] = $subscriber
        ->withSubscriptions()
        ->asArray();
    }

    $listing_data['filters']['segment'] = $this->wp->applyFilters(
      'mailpoet_subscribers_listings_filters_segments',
      $listing_data['filters']['segment']
    );
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

    $recaptcha = $this->settings->get('re_captcha');

    if (!$form instanceof Form) {
      return $this->badRequest(array(
        APIError::BAD_REQUEST => WPFunctions::get()->__('Please specify a valid form ID.', 'mailpoet')
      ));
    }
    if (!empty($data['email'])) {
      return $this->badRequest(array(
        APIError::BAD_REQUEST => WPFunctions::get()->__('Please leave the first field empty.', 'mailpoet')
      ));
    }

    if (!empty($recaptcha['enabled']) && empty($data['recaptcha'])) {
      return $this->badRequest(array(
        APIError::BAD_REQUEST => WPFunctions::get()->__('Please check the CAPTCHA.', 'mailpoet')
      ));
    }

    if (!empty($recaptcha['enabled'])) {
      $res = empty($data['recaptcha']) ? $data['recaptcha-no-js'] : $data['recaptcha'];
      $res = WPFunctions::get()->wpRemotePost('https://www.google.com/recaptcha/api/siteverify', array(
        'body' => array(
          'secret' => $recaptcha['secret_token'],
          'response' => $res
        )
      ));
      if (is_wp_error($res)) {
        return $this->badRequest(array(
          APIError::BAD_REQUEST => WPFunctions::get()->__('Error while validating the CAPTCHA.', 'mailpoet')
        ));
      }
      $res = json_decode(wp_remote_retrieve_body($res));
      if (empty($res->success)) {
        return $this->badRequest(array(
          APIError::BAD_REQUEST => WPFunctions::get()->__('Error while validating the CAPTCHA.', 'mailpoet')
        ));
      }
    }

    $data = $this->deobfuscateFormPayload($data);

    try {
      $this->required_custom_field_validator->validate($data, $form);
    } catch (\Exception $e) {
      return $this->badRequest([APIError::BAD_REQUEST => $e->getMessage()]);
    }

    $segment_ids = (!empty($data['segments'])
      ? (array)$data['segments']
      : array()
    );
    $segment_ids = $form->filterSegments($segment_ids);
    unset($data['segments']);

    if (empty($segment_ids)) {
      return $this->badRequest(array(
        APIError::BAD_REQUEST => WPFunctions::get()->__('Please select a list.', 'mailpoet')
      ));
    }

    // only accept fields defined in the form
    $form_fields = $form->getFieldList();
    $data = array_intersect_key($data, array_flip($form_fields));

    // make sure we don't allow too many subscriptions with the same ip address
    $timeout = SubscriptionThrottling::throttle();

    if ($timeout > 0) {
      throw new \Exception(sprintf(__('You need to wait %d seconds before subscribing again.', 'mailpoet'), $timeout));
    }

    $subscriber = $this->subscriber_actions->subscribe($data, $segment_ids);
    $errors = $subscriber->getErrors();

    if ($errors !== false) {
      return $this->badRequest($errors);
    } else {
      $meta = array();

      if ($form !== false) {
        // record form statistics
        StatisticsForms::record($form->id, $subscriber->id);

        $form = $form->asArray();

        if (!empty($form['settings']['on_success'])) {
          if ($form['settings']['on_success'] === 'page') {
            // redirect to a page on a success, pass the page url in the meta
            $meta['redirect_url'] = WPFunctions::get()->getPermalink($form['settings']['success_page']);
          } else if ($form['settings']['on_success'] === 'url') {
            $meta['redirect_url'] = $form['settings']['success_url'];
          }
        }
      }

      return $this->successResponse(
        array(),
        $meta
      );
    }
  }

  private function deobfuscateFormPayload($data) {
    $obfuscator = new FieldNameObfuscator();
    return $obfuscator->deobfuscateFormPayload($data);
  }

  function save($data = array()) {
    if (empty($data['segments'])) {
      $data['segments'] = array();
    }
    $new_segments = $this->findNewSegments($data);
    $subscriber = Subscriber::createOrUpdate($data);
    $errors = $subscriber->getErrors();

    if (!empty($errors)) {
      return $this->badRequest($errors);
    }

    if ($subscriber->isNew()) {
      $subscriber = Source::setSource($subscriber, Source::ADMINISTRATOR);
      $subscriber->save();
    }

    if (!empty($new_segments)) {
      Scheduler::scheduleSubscriberWelcomeNotification($subscriber->id, $new_segments);
    }

    return $this->successResponse(
      Subscriber::findOne($subscriber->id)->asArray()
    );
  }

  private function findNewSegments(array $data) {
    $old_segment_ids = [];
    if (isset($data['id']) && (int)$data['id'] > 0) {
      $old_segments = SubscriberSegment::where('subscriber_id', $data['id'])->findMany();
      foreach ($old_segments as $old_segment) {
        $old_segment_ids[] = $old_segment->segment_id;
      }
    }
    return array_diff($data['segments'], $old_segment_ids);
  }

  function restore($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $subscriber = Subscriber::findOne($id);
    if ($subscriber instanceof Subscriber) {
      $subscriber->restore();
      $subscriber = Subscriber::findOne($subscriber->id);
      if(!$subscriber instanceof Subscriber) return $this->errorResponse();
      return $this->successResponse(
        $subscriber->asArray(),
        array('count' => 1)
      );
    } else {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => WPFunctions::get()->__('This subscriber does not exist.', 'mailpoet')
      ));
    }
  }

  function trash($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $subscriber = Subscriber::findOne($id);
    if ($subscriber instanceof Subscriber) {
      $subscriber->trash();
      $subscriber = Subscriber::findOne($subscriber->id);
      if(!$subscriber instanceof Subscriber) return $this->errorResponse();
      return $this->successResponse(
        $subscriber->asArray(),
        array('count' => 1)
      );
    } else {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => WPFunctions::get()->__('This subscriber does not exist.', 'mailpoet')
      ));
    }
  }

  function delete($data = array()) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $subscriber = Subscriber::findOne($id);
    if ($subscriber instanceof Subscriber) {
      $subscriber->delete();
      return $this->successResponse(null, array('count' => 1));
    } else {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => WPFunctions::get()->__('This subscriber does not exist.', 'mailpoet')
      ));
    }
  }

  function bulkAction($data = array()) {
    try {
      if (!isset($data['listing']['filter']['segment'])) {
        return $this->successResponse(
          null,
          $this->bulk_action_controller->apply('\MailPoet\Models\Subscriber', $data)
        );
      } else {
        $bulk_action = new BulkAction($data);
        return $this->successResponse(null, $bulk_action->apply());
      }
    } catch (\Exception $e) {
      return $this->errorResponse(array(
        $e->getCode() => $e->getMessage()
      ));
    }
  }
}

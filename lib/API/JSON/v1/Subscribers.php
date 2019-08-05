<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\Response as APIResponse;
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
use MailPoet\Subscription\Captcha;
use MailPoet\Subscription\CaptchaSession;
use MailPoet\Subscription\Throttling as SubscriptionThrottling;
use MailPoet\Subscription\Url as SubscriptionUrl;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class Subscribers extends APIEndpoint {
  const SUBSCRIPTION_LIMIT_COOLDOWN = 60;

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_SUBSCRIBERS,
    'methods' => ['subscribe' => AccessControl::NO_ACCESS_RESTRICTION],
  ];

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

  /** @var Captcha */
  private $subscription_captcha;

  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  /** @var CaptchaSession */
  private $captcha_session;

  public function __construct(
    Listing\BulkActionController $bulk_action_controller,
    SubscribersListings $subscribers_listings,
    SubscriberActions $subscriber_actions,
    RequiredCustomFieldValidator $required_custom_field_validator,
    Listing\Handler $listing_handler,
    Captcha $subscription_captcha,
    WPFunctions $wp,
    SettingsController $settings,
    CaptchaSession $captcha_session
  ) {
    $this->bulk_action_controller = $bulk_action_controller;
    $this->subscribers_listings = $subscribers_listings;
    $this->subscriber_actions = $subscriber_actions;
    $this->required_custom_field_validator = $required_custom_field_validator;
    $this->listing_handler = $listing_handler;
    $this->subscription_captcha = $subscription_captcha;
    $this->wp = $wp;
    $this->settings = $settings;
    $this->captcha_session = $captcha_session;
  }

  function get($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $subscriber = Subscriber::findOne($id);
    if ($subscriber === false) {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This subscriber does not exist.', 'mailpoet'),
      ]);
    } else {
      return $this->successResponse(
        $subscriber
          ->withCustomFields()
          ->withSubscriptions()
          ->asArray()
      );
    }
  }

  function listing($data = []) {

    if (!isset($data['filter']['segment'])) {
      $listing_data = $this->listing_handler->get('\MailPoet\Models\Subscriber', $data);
    } else {
      $listing_data = $this->subscribers_listings->getListingsInSegment($data);
    }

    $result = [];
    foreach ($listing_data['items'] as $subscriber) {
      $subscriber_result = $subscriber
        ->withSubscriptions()
        ->asArray();
      if (isset($data['filter']['segment'])) {
        $subscriber_result = $this->preferUnsubscribedStatusFromSegment($subscriber_result, $data['filter']['segment']);
      }
      $result[] = $subscriber_result;
    }

    $listing_data['filters']['segment'] = $this->wp->applyFilters(
      'mailpoet_subscribers_listings_filters_segments',
      $listing_data['filters']['segment']
    );

    return $this->successResponse($result, [
      'count' => $listing_data['count'],
      'filters' => $listing_data['filters'],
      'groups' => $listing_data['groups'],
    ]);
  }

  private function preferUnsubscribedStatusFromSegment(array $subscriber, $segment_id) {
    $segment_status = $this->findSegmentStatus($subscriber, $segment_id);

    if ($segment_status === Subscriber::STATUS_UNSUBSCRIBED) {
      $subscriber['status'] = $segment_status;
    }
    return $subscriber;
  }

  private function findSegmentStatus(array $subscriber, $segment_id) {
    foreach ($subscriber['subscriptions'] as $segment) {
      if ($segment['segment_id'] === $segment_id) {
        return $segment['status'];
      }
    }
  }

  function subscribe($data = []) {
    $form_id = (isset($data['form_id']) ? (int)$data['form_id'] : false);
    $form = Form::findOne($form_id);
    unset($data['form_id']);

    if (!$form instanceof Form) {
      return $this->badRequest([
        APIError::BAD_REQUEST => WPFunctions::get()->__('Please specify a valid form ID.', 'mailpoet'),
      ]);
    }
    if (!empty($data['email'])) {
      return $this->badRequest([
        APIError::BAD_REQUEST => WPFunctions::get()->__('Please leave the first field empty.', 'mailpoet'),
      ]);
    }

    $captcha_settings = $this->settings->get('captcha');

    if (!empty($captcha_settings['type'])
      && $captcha_settings['type'] === Captcha::TYPE_BUILTIN
      && $this->captcha_session->isAvailable()
    ) {
      if (!isset($data['captcha'])) {
        // Save form data to session
        $this->captcha_session->setFormData(array_merge($data, ['form_id' => $form_id]));
      } elseif ($this->captcha_session->getFormData()) {
        // Restore form data from session
        $data = array_merge($this->captcha_session->getFormData(), ['captcha' => $data['captcha']]);
      }
      // Otherwise use the post data
    }

    $data = $this->deobfuscateFormPayload($data);

    try {
      $this->required_custom_field_validator->validate($data, $form);
    } catch (\Exception $e) {
      return $this->badRequest([APIError::BAD_REQUEST => $e->getMessage()]);
    }

    $segment_ids = (!empty($data['segments'])
      ? (array)$data['segments']
      : []
    );
    $segment_ids = $form->filterSegments($segment_ids);
    unset($data['segments']);

    if (empty($segment_ids)) {
      return $this->badRequest([
        APIError::BAD_REQUEST => WPFunctions::get()->__('Please select a list.', 'mailpoet'),
      ]);
    }

    $captcha_validation_result = $this->validateCaptcha($captcha_settings, $data);
    if ($captcha_validation_result instanceof APIResponse) {
      return $captcha_validation_result;
    }

    // only accept fields defined in the form
    $form_fields = $form->getFieldList();
    $data = array_intersect_key($data, array_flip($form_fields));

    // make sure we don't allow too many subscriptions with the same ip address
    $timeout = SubscriptionThrottling::throttle();

    if ($timeout > 0) {
      $time_to_wait = SubscriptionThrottling::secondsToTimeString($timeout);
      $meta = [];
      $meta['refresh_captcha'] = true;
      return $this->badRequest([
        APIError::BAD_REQUEST => sprintf(WPFunctions::get()->__('You need to wait %s before subscribing again.', 'mailpoet'), $time_to_wait),
      ], $meta);
    }

    $subscriber = $this->subscriber_actions->subscribe($data, $segment_ids);
    $errors = $subscriber->getErrors();

    if ($errors !== false) {
      return $this->badRequest($errors);
    } else {
      if (!empty($captcha_settings['type']) && $captcha_settings['type'] === Captcha::TYPE_BUILTIN && $this->captcha_session->isAvailable()) {
        // Captcha has been verified, invalidate the session vars
        $this->captcha_session->reset();
      }

      $meta = [];

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
        [],
        $meta
      );
    }
  }

  private function deobfuscateFormPayload($data) {
    $obfuscator = new FieldNameObfuscator();
    return $obfuscator->deobfuscateFormPayload($data);
  }

  private function validateCaptcha($captcha_settings, $data) {
    if (empty($captcha_settings['type'])) {
      return true;
    }

    $is_builtin_captcha_required = false;
    if ($captcha_settings['type'] === Captcha::TYPE_BUILTIN && $this->captcha_session->isAvailable()) {
      $is_builtin_captcha_required = $this->subscription_captcha->isRequired(isset($data['email']) ? $data['email'] : '');
      if ($is_builtin_captcha_required && empty($data['captcha'])) {
        $meta = [];
        $meta['redirect_url'] = SubscriptionUrl::getCaptchaUrl();
        return $this->badRequest([
          APIError::BAD_REQUEST => WPFunctions::get()->__('Please check the CAPTCHA.', 'mailpoet'),
        ], $meta);
      }
    }

    if ($captcha_settings['type'] === Captcha::TYPE_RECAPTCHA && empty($data['recaptcha'])) {
      return $this->badRequest([
        APIError::BAD_REQUEST => WPFunctions::get()->__('Please check the CAPTCHA.', 'mailpoet'),
      ]);
    }

    if ($captcha_settings['type'] === Captcha::TYPE_RECAPTCHA) {
      $res = empty($data['recaptcha']) ? $data['recaptcha-no-js'] : $data['recaptcha'];
      $res = WPFunctions::get()->wpRemotePost('https://www.google.com/recaptcha/api/siteverify', [
        'body' => [
          'secret' => $captcha_settings['recaptcha_secret_token'],
          'response' => $res,
        ],
      ]);
      if (is_wp_error($res)) {
        return $this->badRequest([
          APIError::BAD_REQUEST => WPFunctions::get()->__('Error while validating the CAPTCHA.', 'mailpoet'),
        ]);
      }
      $res = json_decode(wp_remote_retrieve_body($res));
      if (empty($res->success)) {
        return $this->badRequest([
          APIError::BAD_REQUEST => WPFunctions::get()->__('Error while validating the CAPTCHA.', 'mailpoet'),
        ]);
      }
    } elseif ($captcha_settings['type'] === Captcha::TYPE_BUILTIN && $is_builtin_captcha_required) {
      $captcha_hash = $this->captcha_session->getCaptchaHash();
      if (empty($captcha_hash)) {
        return $this->badRequest([
          APIError::BAD_REQUEST => WPFunctions::get()->__('Please regenerate the CAPTCHA.', 'mailpoet'),
        ]);
      } elseif (!hash_equals(strtolower($data['captcha']), $captcha_hash)) {
        $this->captcha_session->setCaptchaHash(null);
        $meta = [];
        $meta['refresh_captcha'] = true;
        return $this->badRequest([
          APIError::BAD_REQUEST => WPFunctions::get()->__('The characters entered do not match with the previous captcha.', 'mailpoet'),
        ], $meta);
      }
    }

    return true;
  }

  function save($data = []) {
    if (empty($data['segments'])) {
      $data['segments'] = [];
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

  function restore($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $subscriber = Subscriber::findOne($id);
    if ($subscriber instanceof Subscriber) {
      $subscriber->restore();
      $subscriber = Subscriber::findOne($subscriber->id);
      if(!$subscriber instanceof Subscriber) return $this->errorResponse();
      return $this->successResponse(
        $subscriber->asArray(),
        ['count' => 1]
      );
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This subscriber does not exist.', 'mailpoet'),
      ]);
    }
  }

  function trash($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $subscriber = Subscriber::findOne($id);
    if ($subscriber instanceof Subscriber) {
      $subscriber->trash();
      $subscriber = Subscriber::findOne($subscriber->id);
      if(!$subscriber instanceof Subscriber) return $this->errorResponse();
      return $this->successResponse(
        $subscriber->asArray(),
        ['count' => 1]
      );
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This subscriber does not exist.', 'mailpoet'),
      ]);
    }
  }

  function delete($data = []) {
    $id = (isset($data['id']) ? (int)$data['id'] : false);
    $subscriber = Subscriber::findOne($id);
    if ($subscriber instanceof Subscriber) {
      $subscriber->delete();
      return $this->successResponse(null, ['count' => 1]);
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => WPFunctions::get()->__('This subscriber does not exist.', 'mailpoet'),
      ]);
    }
  }

  function bulkAction($data = []) {
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
      return $this->errorResponse([
        $e->getCode() => $e->getMessage(),
      ]);
    }
  }
}

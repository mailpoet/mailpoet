<?php
namespace MailPoet\API\MP\v1;

use MailPoet\CustomFields\ApiDataSanitizer;
use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\Subscribers\RequiredCustomFieldValidator;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Subscribers\NewSubscriberNotificationMailer;
use MailPoet\Subscribers\Source;
use MailPoet\Tasks\Sending;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class API {

  /** @var NewSubscriberNotificationMailer */
  private $new_subscriber_notification_mailer;

  /** @var ConfirmationEmailMailer */
  private $confirmation_email_mailer;

  /** @var RequiredCustomFieldValidator */
  private $required_custom_field_validator;

  /** @var ApiDataSanitizer */
  private $custom_fields_data_sanitizer;

  public function __construct(
    NewSubscriberNotificationMailer $new_subscriber_notification_mailer,
    ConfirmationEmailMailer $confirmation_email_mailer,
    RequiredCustomFieldValidator $required_custom_field_validator,
    ApiDataSanitizer $custom_fields_data_sanitizer
  ) {
    $this->new_subscriber_notification_mailer = $new_subscriber_notification_mailer;
    $this->confirmation_email_mailer = $confirmation_email_mailer;
    $this->required_custom_field_validator = $required_custom_field_validator;
    $this->custom_fields_data_sanitizer = $custom_fields_data_sanitizer;
  }

  function getSubscriberFields() {
    $data = [
      [
        'id' => 'email',
        'name' => WPFunctions::get()->__('Email', 'mailpoet'),
        'type' => 'text',
        'params' => [
          'required' => '1',
        ],
      ],
      [
        'id' => 'first_name',
        'name' => WPFunctions::get()->__('First name', 'mailpoet'),
        'type' => 'text',
        'params' => [
          'required' => '',
        ],
      ],
      [
        'id' => 'last_name',
        'name' => WPFunctions::get()->__('Last name', 'mailpoet'),
        'type' => 'text',
        'params' => [
          'required' => '',
        ],
      ],
    ];

    $custom_fields = CustomField::selectMany(['id', 'name', 'type', 'params'])->findMany();
    foreach ($custom_fields as $custom_field) {
      $result = [
        'id' => 'cf_' . $custom_field->id,
        'name' => $custom_field->name,
        'type' => $custom_field->type,
      ];
      if (is_serialized($custom_field->params)) {
        $result['params'] = unserialize($custom_field->params);
      } else {
        $result['params'] = $custom_field->params;
      }
      $data[] = $result;
    }

    return $data;
  }

  function addSubscriberField(array $data = []) {
    try {
      $custom_field = CustomField::createOrUpdate($this->custom_fields_data_sanitizer->sanitize($data));
      $errors = $custom_field->getErrors();
      if (!empty($errors)) {
        throw new APIException('Failed to save a new subscriber field ' . join(', ', $errors), APIException::FAILED_TO_SAVE_SUBSCRIBER_FIELD);
      }
      $custom_field = CustomField::findOne($custom_field->id);
      if (!$custom_field instanceof CustomField) {
        throw new APIException('Failed to create a new subscriber field', APIException::FAILED_TO_SAVE_SUBSCRIBER_FIELD);
      }
      return $custom_field->asArray();
    } catch (\InvalidArgumentException $e) {
      throw new APIException($e->getMessage(), $e->getCode(), $e);
    }
  }

  function subscribeToList($subscriber_id, $list_id, $options = []) {
    return $this->subscribeToLists($subscriber_id, [$list_id], $options);
  }

  function subscribeToLists($subscriber_id, array $list_ids, $options = []) {
    $schedule_welcome_email = (isset($options['schedule_welcome_email']) && $options['schedule_welcome_email'] === false) ? false : true;
    $send_confirmation_email = (isset($options['send_confirmation_email']) && $options['send_confirmation_email'] === false) ? false : true;
    $skip_subscriber_notification = (isset($options['skip_subscriber_notification']) && $options['skip_subscriber_notification'] === true) ? true : false;

    if (empty($list_ids)) {
      throw new APIException(__('At least one segment ID is required.', 'mailpoet'), APIException::SEGMENT_REQUIRED);
    }

    // throw exception when subscriber does not exist
    $subscriber = Subscriber::findOne($subscriber_id);
    if (!$subscriber) {
      throw new APIException(__('This subscriber does not exist.', 'mailpoet'), APIException::SUBSCRIBER_NOT_EXISTS);
    }

    // throw exception when none of the segments exist
    $found_segments = Segment::whereIn('id', $list_ids)->findMany();
    if (!$found_segments) {
      $exception = WPFunctions::get()->_n('This list does not exist.', 'These lists do not exist.', count($list_ids), 'mailpoet');
      throw new APIException($exception, APIException::LIST_NOT_EXISTS);
    }

    // throw exception when trying to subscribe to WP Users or WooCommerce Customers segments
    $found_segments_ids = [];
    foreach ($found_segments as $found_segment) {
      if ($found_segment->type === Segment::TYPE_WP_USERS) {
        throw new APIException(__(sprintf("Can't subscribe to a WordPress Users list with ID %d.", $found_segment->id), 'mailpoet'), APIException::SUBSCRIBING_TO_WP_LIST_NOT_ALLOWED);
      }
      if ($found_segment->type === Segment::TYPE_WC_USERS) {
        throw new APIException(__(sprintf("Can't subscribe to a WooCommerce Customers list with ID %d.", $found_segment->id), 'mailpoet'), APIException::SUBSCRIBING_TO_WC_LIST_NOT_ALLOWED);
      }
      if ($found_segment->type !== Segment::TYPE_DEFAULT) {
        throw new APIException(__(sprintf("Can't subscribe to a list with ID %d.", $found_segment->id), 'mailpoet'), APIException::SUBSCRIBING_TO_LIST_NOT_ALLOWED);
      }
      $found_segments_ids[] = $found_segment->id;
    }

    // throw an exception when one or more segments do not exist
    if (count($found_segments_ids) !== count($list_ids)) {
      $missing_ids = array_values(array_diff($list_ids, $found_segments_ids));
      $exception = sprintf(
        WPFunctions::get()->_n('List with ID %s does not exist.', 'Lists with IDs %s do not exist.', count($missing_ids), 'mailpoet'),
        implode(', ', $missing_ids)
      );
      throw new APIException(sprintf($exception, implode(', ', $missing_ids)), APIException::LIST_NOT_EXISTS);
    }

    SubscriberSegment::subscribeToSegments($subscriber, $found_segments_ids);

    // schedule welcome email
    if ($schedule_welcome_email && $subscriber->status === Subscriber::STATUS_SUBSCRIBED) {
      $this->_scheduleWelcomeNotification($subscriber, $found_segments_ids);
    }

    // send confirmation email
    if (
      $send_confirmation_email
      && $subscriber->status === Subscriber::STATUS_UNCONFIRMED
      && (int)$subscriber->count_confirmations === 0
    ) {
      $result = $this->_sendConfirmationEmail($subscriber);
      if (!$result && $subscriber->getErrors()) {
        throw new APIException(
          WPFunctions::get()->__(sprintf('Subscriber added to lists, but confirmation email failed to send: %s', strtolower(implode(', ', $subscriber->getErrors()))), 'mailpoet'),
        APIException::CONFIRMATION_FAILED_TO_SEND);
      }
    }

    if (!$skip_subscriber_notification && ($subscriber->status === Subscriber::STATUS_SUBSCRIBED)) {
      $this->sendSubscriberNotification($subscriber, $found_segments_ids);
    }

    return $subscriber->withCustomFields()->withSubscriptions()->asArray();
  }

  function unsubscribeFromList($subscriber_id, $list_id) {
    return $this->unsubscribeFromLists($subscriber_id, [$list_id]);
  }

  function unsubscribeFromLists($subscriber_id, array $list_ids) {
    if (empty($list_ids)) {
      throw new APIException(__('At least one segment ID is required.', 'mailpoet'), APIException::SEGMENT_REQUIRED);
    }

    // throw exception when subscriber does not exist
    $subscriber = Subscriber::findOne($subscriber_id);
    if (!$subscriber) {
      throw new APIException(__('This subscriber does not exist.', 'mailpoet'), APIException::SUBSCRIBER_NOT_EXISTS);
    }

    // throw exception when none of the segments exist
    $found_segments = Segment::whereIn('id', $list_ids)->findMany();
    if (!$found_segments) {
      $exception = WPFunctions::get()->_n('This list does not exist.', 'These lists do not exist.', count($list_ids), 'mailpoet');
      throw new APIException($exception, APIException::LIST_NOT_EXISTS);
    }

    // throw exception when trying to subscribe to WP Users or WooCommerce Customers segments
    $found_segments_ids = [];
    foreach ($found_segments as $segment) {
      if ($segment->type === Segment::TYPE_WP_USERS) {
        throw new APIException(__(sprintf("Can't unsubscribe from a WordPress Users list with ID %d.", $segment->id), 'mailpoet'), APIException::SUBSCRIBING_TO_WP_LIST_NOT_ALLOWED);
      }
      if ($segment->type === Segment::TYPE_WC_USERS) {
        throw new APIException(__(sprintf("Can't unsubscribe from a WooCommerce Customers list with ID %d.", $segment->id), 'mailpoet'), APIException::SUBSCRIBING_TO_WC_LIST_NOT_ALLOWED);
      }
      $found_segments_ids[] = $segment->id;
    }

    // throw an exception when one or more segments do not exist
    if (count($found_segments_ids) !== count($list_ids)) {
      $missing_ids = array_values(array_diff($list_ids, $found_segments_ids));
      $exception = sprintf(
        WPFunctions::get()->_n('List with ID %s does not exist.', 'Lists with IDs %s do not exist.', count($missing_ids), 'mailpoet'),
        implode(', ', $missing_ids)
      );
      throw new APIException($exception, APIException::LIST_NOT_EXISTS);
    }

    SubscriberSegment::unsubscribeFromSegments($subscriber, $found_segments_ids);
    return $subscriber->withCustomFields()->withSubscriptions()->asArray();
  }

  function getLists() {
    return Segment::where('type', Segment::TYPE_DEFAULT)
      ->findArray();
  }

  function addSubscriber(array $subscriber, $list_ids = [], $options = []) {
    $send_confirmation_email = (isset($options['send_confirmation_email']) && $options['send_confirmation_email'] === false) ? false : true;
    $schedule_welcome_email = (isset($options['schedule_welcome_email']) && $options['schedule_welcome_email'] === false) ? false : true;
    $skip_subscriber_notification = (isset($options['skip_subscriber_notification']) && $options['skip_subscriber_notification'] === true) ? true : false;

    // throw exception when subscriber email is missing
    if (empty($subscriber['email'])) {
      throw new APIException(
        WPFunctions::get()->__('Subscriber email address is required.', 'mailpoet'),
        APIException::EMAIL_ADDRESS_REQUIRED
      );
    }

    // throw exception when subscriber already exists
    if (Subscriber::findOne($subscriber['email'])) {
      throw new APIException(
        WPFunctions::get()->__('This subscriber already exists.', 'mailpoet'),
        APIException::SUBSCRIBER_EXISTS
      );
    }

    // separate data into default and custom fields
    list($default_fields, $custom_fields) = Subscriber::extractCustomFieldsFromFromObject($subscriber);

    // filter out all incoming data that we don't want to change, like status, ip address, ...
    $default_fields = array_intersect_key($default_fields, array_flip(['email', 'first_name', 'last_name']));

    // if some required default fields are missing, set their values
    $default_fields = Subscriber::setRequiredFieldsDefaultValues($default_fields);

    $this->required_custom_field_validator->validate($custom_fields);

    // add subscriber
    $new_subscriber = Subscriber::create();
    $new_subscriber->hydrate($default_fields);
    $new_subscriber = Source::setSource($new_subscriber, Source::API);
    $new_subscriber->save();
    if ($new_subscriber->getErrors() !== false) {
      throw new APIException(
        WPFunctions::get()->__(sprintf('Failed to add subscriber: %s', strtolower(implode(', ', $new_subscriber->getErrors()))), 'mailpoet'),
        APIException::FAILED_TO_SAVE_SUBSCRIBER
      );
    }
    if (!empty($custom_fields)) {
      $new_subscriber->saveCustomFields($custom_fields);
    }

    // reload subscriber to get the saved status/created|updated|delete dates/other fields
    $new_subscriber = Subscriber::findOne($new_subscriber->id);

    // subscribe to segments and optionally: 1) send confirmation email, 2) schedule welcome email(s)
    if (!empty($list_ids)) {
      $this->subscribeToLists($new_subscriber->id, $list_ids, [
        'send_confirmation_email' => $send_confirmation_email,
        'schedule_welcome_email' => $schedule_welcome_email,
        'skip_subscriber_notification' => $skip_subscriber_notification,
      ]);

      // schedule welcome email(s)
      if ($schedule_welcome_email && $new_subscriber->status === Subscriber::STATUS_SUBSCRIBED) {
        $this->_scheduleWelcomeNotification($new_subscriber, $list_ids);
      }

      if (!$skip_subscriber_notification && ($new_subscriber->status === Subscriber::STATUS_SUBSCRIBED)) {
        $this->sendSubscriberNotification($new_subscriber, $list_ids);
      }
    }
    return $new_subscriber->withCustomFields()->withSubscriptions()->asArray();
  }

  function addList(array $list) {
    // throw exception when list name is missing
    if (empty($list['name'])) {
      throw new APIException(
        WPFunctions::get()->__('List name is required.', 'mailpoet'),
        APIException::LIST_NAME_REQUIRED
      );
    }

    // throw exception when list already exists
    if (Segment::where('name', $list['name'])->findOne()) {
      throw new APIException(
        WPFunctions::get()->__('This list already exists.', 'mailpoet'),
        APIException::LIST_EXISTS
      );
    }

    // filter out all incoming data that we don't want to change, like type,
    $list = array_intersect_key($list, array_flip(['name', 'description']));

    // add list
    $new_list = Segment::create();
    $new_list->hydrate($list);
    $new_list->save();
    if ($new_list->getErrors() !== false) {
      throw new APIException(
        WPFunctions::get()->__(sprintf('Failed to add list: %s', strtolower(implode(', ', $new_list->getErrors()))), 'mailpoet'),
        APIException::FAILED_TO_SAVE_LIST
      );
    }

    // reload list to get the saved created|updated|delete dates/other fields
    $new_list = Segment::findOne($new_list->id);
    if (!$new_list instanceof Segment) {
      throw new APIException(WPFunctions::get()->__('Failed to add list', 'mailpoet'), APIException::FAILED_TO_SAVE_LIST);
    }

    return $new_list->asArray();
  }

  function getSubscriber($subscriber_email) {
    $subscriber = Subscriber::findOne($subscriber_email);
    // throw exception when subscriber does not exist
    if (!$subscriber) {
      throw new APIException(__('This subscriber does not exist.', 'mailpoet'), APIException::SUBSCRIBER_NOT_EXISTS);
    }
    return $subscriber->withCustomFields()->withSubscriptions()->asArray();
  }

  protected function _sendConfirmationEmail(Subscriber $subscriber) {
    return $this->confirmation_email_mailer->sendConfirmationEmail($subscriber);
  }

  protected function _scheduleWelcomeNotification(Subscriber $subscriber, array $segments) {
    $result = Scheduler::scheduleSubscriberWelcomeNotification($subscriber->id, $segments);
    if (is_array($result)) {
      foreach ($result as $queue) {
        if ($queue instanceof Sending && $queue->getErrors()) {
          throw new APIException(
            WPFunctions::get()->__(sprintf('Subscriber added, but welcome email failed to send: %s', strtolower(implode(', ', $queue->getErrors()))), 'mailpoet'),
            APIException::WELCOME_FAILED_TO_SEND
          );
        }
      }
    }
    return $result;
  }

  private function sendSubscriberNotification(Subscriber $subscriber, array $segment_ids) {
    $this->new_subscriber_notification_mailer->send($subscriber, Segment::whereIn('id', $segment_ids)->findMany());
  }
}

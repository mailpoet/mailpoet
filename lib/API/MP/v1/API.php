<?php
namespace MailPoet\API\MP\v1;

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

  public function __construct(
    NewSubscriberNotificationMailer $new_subscriber_notification_mailer,
    ConfirmationEmailMailer $confirmation_email_mailer,
    RequiredCustomFieldValidator $required_custom_field_validator
  ) {
    $this->new_subscriber_notification_mailer = $new_subscriber_notification_mailer;
    $this->confirmation_email_mailer = $confirmation_email_mailer;
    $this->required_custom_field_validator = $required_custom_field_validator;
  }

  function getSubscriberFields() {
    $data = array(
      array(
        'id' => 'email',
        'name' => WPFunctions::get()->__('Email', 'mailpoet')
      ),
      array(
        'id' => 'first_name',
        'name' => WPFunctions::get()->__('First name', 'mailpoet')
      ),
      array(
        'id' => 'last_name',
        'name' => WPFunctions::get()->__('Last name', 'mailpoet')
      )
    );

    $custom_fields = CustomField::selectMany(array('id', 'name'))->findMany();
    foreach ($custom_fields as $custom_field) {
      $data[] = array(
        'id' => 'cf_' . $custom_field->id,
        'name' => $custom_field->name
      );
    }

    return $data;
  }

  function subscribeToList($subscriber_id, $segment_id, $options = array()) {
    return $this->subscribeToLists($subscriber_id, array($segment_id), $options);
  }

  function subscribeToLists($subscriber_id, array $segments_ids, $options = array()) {
    $schedule_welcome_email = (isset($options['schedule_welcome_email']) && $options['schedule_welcome_email'] === false) ? false : true;

    if (empty($segments_ids)) {
      throw new \Exception(__('At least one segment ID is required.', 'mailpoet'));
    }

    // throw exception when subscriber does not exist
    $subscriber = Subscriber::findOne($subscriber_id);
    if (!$subscriber) {
      throw new \Exception(__('This subscriber does not exist.', 'mailpoet'));
    }

    // throw exception when none of the segments exist
    $found_segments = Segment::whereIn('id', $segments_ids)->findMany();
    if (!$found_segments) {
      $exception = WPFunctions::get()->n('This list does not exist.', 'These lists do not exist.', count($segments_ids), 'mailpoet');
      throw new \Exception($exception);
    }

    // throw exception when trying to subscribe to WP Users or WooCommerce Customers segments
    $found_segments_ids = array();
    foreach ($found_segments as $found_segment) {
      if ($found_segment->type === Segment::TYPE_WP_USERS) {
        throw new \Exception(__(sprintf("Can't subscribe to a WordPress Users list with ID %d.", $found_segment->id), 'mailpoet'));
      }
      if ($found_segment->type === Segment::TYPE_WC_USERS) {
        throw new \Exception(__(sprintf("Can't subscribe to a WooCommerce Customers list with ID %d.", $found_segment->id), 'mailpoet'));
      }
      $found_segments_ids[] = $found_segment->id;
    }

    // throw an exception when one or more segments do not exist
    if (count($found_segments_ids) !== count($segments_ids)) {
      $missing_ids = array_values(array_diff($segments_ids, $found_segments_ids));
      $exception = sprintf(
        WPFunctions::get()->n('List with ID %s does not exist.', 'Lists with IDs %s do not exist.', count($missing_ids), 'mailpoet'),
        implode(', ', $missing_ids)
      );
      throw new \Exception(sprintf($exception, implode(', ', $missing_ids)));
    }

    SubscriberSegment::subscribeToSegments($subscriber, $found_segments_ids);

    // schedule welcome email
    if ($schedule_welcome_email && $subscriber->status === Subscriber::STATUS_SUBSCRIBED) {
      $this->_scheduleWelcomeNotification($subscriber, $found_segments_ids);
    }

    return $subscriber->withCustomFields()->withSubscriptions()->asArray();
  }

  function unsubscribeFromList($subscriber_id, $segment_id) {
    return $this->unsubscribeFromLists($subscriber_id, array($segment_id));
  }

  function unsubscribeFromLists($subscriber_id, array $segments_ids) {
    if (empty($segments_ids)) {
      throw new \Exception(__('At least one segment ID is required.', 'mailpoet'));
    }

    // throw exception when subscriber does not exist
    $subscriber = Subscriber::findOne($subscriber_id);
    if (!$subscriber) {
      throw new \Exception(__('This subscriber does not exist.', 'mailpoet'));
    }

    // throw exception when none of the segments exist
    $found_segments = Segment::whereIn('id', $segments_ids)->findMany();
    if (!$found_segments) {
      $exception = WPFunctions::get()->n('This list does not exist.', 'These lists do not exist.', count($segments_ids), 'mailpoet');
      throw new \Exception($exception);
    }

    // throw exception when trying to subscribe to WP Users or WooCommerce Customers segments
    $found_segments_ids = array();
    foreach ($found_segments as $segment) {
      if ($segment->type === Segment::TYPE_WP_USERS) {
        throw new \Exception(__(sprintf("Can't unsubscribe from a WordPress Users list with ID %d.", $segment->id), 'mailpoet'));
      }
      if ($segment->type === Segment::TYPE_WC_USERS) {
        throw new \Exception(__(sprintf("Can't unsubscribe from a WooCommerce Customers list with ID %d.", $segment->id), 'mailpoet'));
      }
      $found_segments_ids[] = $segment->id;
    }

    // throw an exception when one or more segments do not exist
    if (count($found_segments_ids) !== count($segments_ids)) {
      $missing_ids = array_values(array_diff($segments_ids, $found_segments_ids));
      $exception = sprintf(
        WPFunctions::get()->n('List with ID %s does not exist.', 'Lists with IDs %s do not exist.', count($missing_ids), 'mailpoet'),
        implode(', ', $missing_ids)
      );
      throw new \Exception($exception);
    }

    SubscriberSegment::unsubscribeFromSegments($subscriber, $found_segments_ids);
    return $subscriber->withCustomFields()->withSubscriptions()->asArray();
  }

  function getLists() {
    return Segment::whereNotIn('type', [Segment::TYPE_WP_USERS, Segment::TYPE_WC_USERS])
      ->findArray();
  }

  function addSubscriber(array $subscriber, $segments = array(), $options = array()) {
    $send_confirmation_email = (isset($options['send_confirmation_email']) && $options['send_confirmation_email'] === false) ? false : true;
    $schedule_welcome_email = (isset($options['schedule_welcome_email']) && $options['schedule_welcome_email'] === false) ? false : true;
    $skip_subscriber_notification = (isset($options['skip_subscriber_notification']) && $options['skip_subscriber_notification'] === true) ? true : false;

    // throw exception when subscriber email is missing
    if (empty($subscriber['email'])) {
      throw new \Exception(
        WPFunctions::get()->__('Subscriber email address is required.', 'mailpoet')
      );
    }

    // throw exception when subscriber already exists
    if (Subscriber::findOne($subscriber['email'])) {
      throw new \Exception(
        WPFunctions::get()->__('This subscriber already exists.', 'mailpoet')
      );
    }

    // separate data into default and custom fields
    list($default_fields, $custom_fields) = Subscriber::extractCustomFieldsFromFromObject($subscriber);
    // if some required default fields are missing, set their values
    $default_fields = Subscriber::setRequiredFieldsDefaultValues($default_fields);

    $this->required_custom_field_validator->validate($custom_fields);

    // add subscriber
    $new_subscriber = Subscriber::create();
    $new_subscriber->hydrate($default_fields);
    $new_subscriber = Source::setSource($new_subscriber, Source::API);
    $new_subscriber->save();
    if ($new_subscriber->getErrors() !== false) {
      throw new \Exception(
        WPFunctions::get()->__(sprintf('Failed to add subscriber: %s', strtolower(implode(', ', $new_subscriber->getErrors()))), 'mailpoet')
      );
    }
    if (!empty($custom_fields)) {
      $new_subscriber->saveCustomFields($custom_fields);
    }

    // reload subscriber to get the saved status/created|updated|delete dates/other fields
    $new_subscriber = Subscriber::findOne($new_subscriber->id);

    // subscribe to segments and optionally: 1) send confirmation email, 2) schedule welcome email(s)
    if (!empty($segments)) {
      $this->subscribeToLists($new_subscriber->id, $segments);

      // send confirmation email
      if ($send_confirmation_email && $new_subscriber->status === Subscriber::STATUS_UNCONFIRMED) {
        $result = $this->_sendConfirmationEmail($new_subscriber);
        if (!$result && $new_subscriber->getErrors()) {
          throw new \Exception(
            WPFunctions::get()->__(sprintf('Subscriber added, but confirmation email failed to send: %s', strtolower(implode(', ', $new_subscriber->getErrors()))), 'mailpoet')
          );
        }
      }

      // schedule welcome email(s)
      if ($schedule_welcome_email && $new_subscriber->status === Subscriber::STATUS_SUBSCRIBED) {
        $this->_scheduleWelcomeNotification($new_subscriber, $segments);
      }

      if (!$skip_subscriber_notification) {
        $this->sendSubscriberNotification($new_subscriber, $segments);
      }
    }
    return $new_subscriber->withCustomFields()->withSubscriptions()->asArray();
  }

  function addList(array $list) {
    // throw exception when list name is missing
    if (empty($list['name'])) {
      throw new \Exception(
        WPFunctions::get()->__('List name is required.', 'mailpoet')
      );
    }

    // throw exception when list already exists
    if (Segment::where('name', $list['name'])->findOne()) {
      throw new \Exception(
        WPFunctions::get()->__('This list already exists.', 'mailpoet')
      );
    }

    // add list
    $new_list = Segment::create();
    $new_list->hydrate($list);
    $new_list->save();
    if ($new_list->getErrors() !== false) {
      throw new \Exception(
        WPFunctions::get()->__(sprintf('Failed to add list: %s', strtolower(implode(', ', $new_list->getErrors()))), 'mailpoet')
      );
    }

    // reload list to get the saved created|updated|delete dates/other fields
    $new_list = Segment::findOne($new_list->id);

    return $new_list->asArray();
  }

  function getSubscriber($subscriber_email) {
    $subscriber = Subscriber::findOne($subscriber_email);
    // throw exception when subscriber does not exist
    if (!$subscriber) {
      throw new \Exception(__('This subscriber does not exist.', 'mailpoet'));
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
          throw new \Exception(
            WPFunctions::get()->__(sprintf('Subscriber added, but welcome email failed to send: %s', strtolower(implode(', ', $queue->getErrors()))), 'mailpoet')
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

<?php
namespace MailPoet\API\MP\v1;

use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Newsletter\Scheduler\Scheduler;

if(!defined('ABSPATH')) exit;

class API {
  function getSubscriberFields() {
    $data = array(
      array(
        'id' => 'email',
        'name' => __('Email', 'mailpoet')
      ),
      array(
        'id' => 'first_name',
        'name' => __('First name', 'mailpoet')
      ),
      array(
        'id' => 'last_name',
        'name' => __('Last name', 'mailpoet')
      )
    );

    $custom_fields = CustomField::selectMany(array('id', 'name'))->findMany();
    foreach($custom_fields as $custom_field) {
      $data[] = array(
        'id' => 'cf_' . $custom_field->id,
        'name' => $custom_field->name
      );
    }

    return $data;
  }

  function subscribeToList($subscriber_id, $segment_id) {
    return $this->subscribeToLists($subscriber_id, array($segment_id));
  }

  function subscribeToLists($subscriber_id, array $segments_ids) {
    $subscriber = Subscriber::findOne($subscriber_id);
    // throw exception when subscriber does not exist
    if(!$subscriber) {
      throw new \Exception(__('This subscriber does not exist.', 'mailpoet'));
    }

    // throw exception when none of the segments exist
    $found_segments = Segment::whereIn('id', $segments_ids)->findMany();
    if(!$found_segments) {
      throw new \Exception(__('These lists do not exist.', 'mailpoet'));
    }

    // throw exception when trying to subscribe to a WP Users segment
    $found_segments_ids = array();
    foreach($found_segments as $found_segment) {
      if($found_segment->type === Segment::TYPE_WP_USERS) {
        throw new \Exception(__(sprintf("Can't subscribe to a WordPress Users list with ID %d.", $found_segment->id), 'mailpoet'));
      }
      $found_segments_ids[] = $found_segment->id;
    }

    // throw an exception when one or more segments do not exist
    if(count($found_segments_ids) !== count($segments_ids)) {
      $missing_ids = array_values(array_diff($segments_ids, $found_segments_ids));
      throw new \Exception(__(sprintf('Lists with ID %s do not exist.', implode(', ', $missing_ids)), 'mailpoet'));
    }

    SubscriberSegment::subscribeToSegments($subscriber, $found_segments_ids);
    return $subscriber->withCustomFields()->withSubscriptions()->asArray();
  }

  function getLists() {
    return Segment::whereNotEqual('type', Segment::TYPE_WP_USERS)->findArray();
  }

  function addSubscriber(array $subscriber, $segments = array(), $options = array()) {
    $send_confirmation_email = (isset($options['send_confirmation_email']) && $options['send_confirmation_email'] === false) ? false : true;
    $schedule_welcome_email = (isset($options['schedule_welcome_email']) && $options['schedule_welcome_email'] === false) ? false : true;

    // throw exception when subscriber email is missing
    if(empty($subscriber['email'])) {
      throw new \Exception(
        __('Subscriber email address is required.', 'mailpoet')
      );
    }

    // throw exception when subscriber already exists
    if(Subscriber::findOne($subscriber['email'])) {
      throw new \Exception(
        __('This subscriber already exists.', 'mailpoet')
      );
    }

    // separate data into default and custom fields
    list($default_fields, $custom_fields) = Subscriber::extractCustomFieldsFromFromObject($subscriber);
    // if some required default fields are missing, set their values
    $default_fields = Subscriber::setRequiredFieldsDefaultValues($default_fields);

    // add subscriber
    $new_subscriber = Subscriber::create();
    $new_subscriber->hydrate($default_fields);
    $new_subscriber->save();
    if($new_subscriber->getErrors() !== false) {
      throw new \Exception(
        __(sprintf('Failed to add subscriber: %s', strtolower(implode(', ', $new_subscriber->getErrors()))), 'mailpoet')
      );
    }
    if(!empty($custom_fields)) {
      $new_subscriber->saveCustomFields($custom_fields);
    }

    // subscribe to segments and optionally: 1) send confirmation email, 2) schedule welcome email(s)
    if(!empty($segments)) {
      $this->subscribeToLists($new_subscriber->id, $segments);

      // send confirmation email
      if($send_confirmation_email && $new_subscriber->status === Subscriber::STATUS_UNCONFIRMED) {
        $this->sendConfirmationEmail($new_subscriber);
      }

      // schedule welcome email(s)
      if($schedule_welcome_email) {
        Scheduler::scheduleSubscriberWelcomeNotification($new_subscriber->id, $segments);
      }
    }
    return $new_subscriber->withCustomFields()->withSubscriptions()->asArray();
  }
}
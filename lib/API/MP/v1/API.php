<?php
namespace MailPoet\API\MP\v1;

use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;

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
    $subscriber = Subscriber::findOne((int)$subscriber_id);
    // throw exception when subscriber does not exist
    if(!$subscriber) {
      throw new \Exception(__('This subscriber does not exist.', 'mailpoet'));
    }

    // throw exception when none of the segments exist
    $found_segments = Segment::whereIn('id', $segments_ids)->findMany();
    if(!$found_segments) {
      throw new \Exception(__('These lists do not exists.', 'mailpoet'));
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

    return SubscriberSegment::subscribeToSegments($subscriber, $found_segments_ids);
  }

  function getLists() {
    return Segment::whereNotEqual('type', Segment::TYPE_WP_USERS)->findArray();
  }
}
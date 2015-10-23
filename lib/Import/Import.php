<?php namespace MailPoet\Import;

use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Util\Helpers;

class Import {

  function getSegments() {
    return Segment::findArray();
  }

  function getSubscriberCustomFields() {
    return CustomField::findArray();
  }

  function getSubscriberFields() {
    return array(
      'subscriber_email' => __("Email"),
      'subscriber_firstname' => __("First name"),
      'subscriber_lastname' => __("Last name"),
      'subscriber_confirmed_ip' => __("IP address"),
      'subscriber_confirmed_at' => __("Subscription date"),
      'subscriber_state' => __("Status")
    );
  }

  function formatSubscriberFields($subscriberFields) {
    return array_map(function ($fieldId, $fieldName) {
      return array(
        'id' => $fieldId,
        'name' => $fieldName,
        'type' => ($fieldId === 'subscriber_confirmed_at') ? 'date' : null,
        'custom' => false
      );
    }, array_keys($subscriberFields), $subscriberFields);
  }

  function formatSubscriberCustomFields($subscriberCustomFields) {
    return array_map(function ($field) {
      return array(
        'id' => $field['id'],
        'name' => $field['name'],
        'label' => $field['name'],
        'type' => $field['type'],
        'custom' => true
      );
    }, $subscriberCustomFields);
  }

  function formatSelect2Fields($subscriberFields, $subscriberCustomFields) {
    $data = array(
      array(
        'name' => __("Actions"),
        'children' => array(
          array(
            'id' => 'ignore',
            'name' => __("Ignore column..."),
          ),
          array(
            'id' => 'create',
            'name' => __("Create new column...")
          ),
        )
      ),
      array(
        'name' => __("System columns"),
        'children' => $subscriberFields
      )
    );

    if($subscriberCustomFields) {
      array_push($data, array(
        'name' => __("User columns"),
        'children' => $subscriberCustomFields
      ));
    }
    return $data;
  }

  function bootstrapImportMenu() {
    $data['segments'] = array_map(function ($segment) {
      return array(
        'id' => $segment['id'],
        'name' => $segment['name'],
        'text' => $segment['name']
      );
    }, $this->getSegments());

    $data['subscriberFields'] = $this->formatSubscriberFields(
      $this->getSubscriberFields()
    );

    $data['subscriberCustomFields'] = $this->formatSubscriberCustomFields(
      $this->getSubscriberCustomFields()
    );

    $data['select2Fields'] = $this->formatSelect2Fields(
      $data['subscriberFields'],
      $data['subscriberCustomFields']
    );

    $data['maximumParseSize'] = Helpers::get_maximum_post_size();
    return array_map('json_encode', $data);
  }
}
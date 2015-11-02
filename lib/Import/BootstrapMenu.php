<?php namespace MailPoet\Import;

use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Util\Helpers;

class BootstrapMenu {

  function __construct() {
    $this->subscriberFields = $this->getSubscriberFields();
    $this->subscriberCustomFields = $this->getSubscriberCustomFields();
    $this->segments = $this->getSegments();
  }

  function getSubscriberFields() {
    return array(
      'subscriber_email' => __("Email"),
      'subscriber_firstname' => __("First name"),
      'subscriber_lastname' => __("Last name"),
/*    'subscriber_confirmed_ip' => __("IP address"),
      'subscriber_confirmed_at' => __("Subscription date"),*/
      'subscriber_state' => __("Status")
    );
  }

  function getSegments() {
    return Segment::findArray();
  }

  function getSubscriberCustomFields() {
    return CustomField::findArray();
  }

  function formatSubscriberFields() {
    return array_map(function ($fieldId, $fieldName) {
      return array(
        'id' => $fieldId,
        'name' => $fieldName,
        'type' => ($fieldId === 'subscriber_confirmed_at') ? 'date' : null,
        'custom' => false
      );
    }, array_keys($this->subscriberFields), $this->subscriberFields);
  }

  function formatSubscriberCustomFields() {
    return array_map(function ($field) {
      return array(
        'id' => $field['id'],
        'name' => $field['name'],
        'label' => $field['name'],
        'type' => $field['type'],
        'custom' => true
      );
    }, $this->subscriberCustomFields);
  }

  function formatSubscriberFieldsSelect2() {
    $select2Fields = array(
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
        'children' => $this->formatSubscriberFields()
      )
    );
    if($this->subscriberCustomFields) {
      array_push($select2Fields, array(
        'name' => __("User columns"),
        'children' => $this->formatSubscriberCustomFields()
    ));
    }
    return $select2Fields;
  }

  function bootstrap() {
    $data['segments'] = array_map(function ($segment) {
      return array(
        'id' => $segment['id'],
        'name' => $segment['name'],
      );
    }, $this->getSegments());

    $data['subscriberFields'] = array_merge(
      $this->formatSubscriberFields(),
      $this->formatSubscriberCustomFields()
    );

    $data['subscriberFieldsSelect2'] = $this->formatSubscriberFieldsSelect2();

    $data = array_map('json_encode', $data);
    $data['maxPostSizeBytes'] = Helpers::getMaxPostSize('bytes');
    $data['maxPostSize'] = Helpers::getMaxPostSize();
    return $data;
  }
}
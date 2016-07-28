<?php
namespace MailPoet\Subscribers\ImportExport;

use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Util\Helpers;

class ImportExportFactory {
  public $action;

  function __construct($action = null) {
    $this->action = $action;
  }

  function getSegments($with_confirmed_subscribers = false) {
    $segments = ($this->action === 'import') ?
      Segment::getSegmentsWithSubscriberCount() :
      Segment::getSegmentsForExport($with_confirmed_subscribers);
    return array_map(function($segment) {
      if(!$segment['name']) $segment['name'] = __('Not In List');
      if(!$segment['id']) $segment['id'] = 0;
      return array(
        'id' => $segment['id'],
        'name' => $segment['name'],
        'subscriberCount' => $segment['subscribers']
      );
    }, $segments);
  }

  function getSubscriberFields() {
    return array(
      'email' => __('Email'),
      'first_name' => __('First name'),
      'last_name' => __('Last name'),
      'status' => __('Status')
      // TODO: add additional fields from MP2
    );
  }

  function formatSubscriberFields($subscriber_fields) {
    return array_map(function($field_id, $field_name) {
      return array(
        'id' => $field_id,
        'name' => $field_name,
        'type' => ($field_id === 'confirmed_at') ? 'date' : null,
        'custom' => false
      );
    }, array_keys($subscriber_fields), $subscriber_fields);
  }

  function getSubscriberCustomFields() {
    return CustomField::findArray();
  }

  function formatSubscriberCustomFields($subscriber_custom_fields) {
    return array_map(function($field) {
      return array(
        'id' => $field['id'],
        'name' => $field['name'],
        'type' => $field['type'],
        'custom' => true
      );
    }, $subscriber_custom_fields);
  }

  function formatFieldsForSelect2(
    $subscriber_fields,
    $subscriber_custom_fields) {
    $actions = ($this->action === 'import') ?
      array(
        array(
          'id' => 'ignore',
          'name' => __('Ignore field...'),
        ),
        array(
          'id' => 'create',
          'name' => __('Create new field...')
        ),
      ) :
      array(
        array(
          'id' => 'select',
          'name' => __('Select all...'),
        ),
        array(
          'id' => 'deselect',
          'name' => __('Deselect all...')
        ),
      );
    $select2Fields = array(
      array(
        'name' => __('Actions'),
        'children' => $actions
      ),
      array(
        'name' => __('System fields'),
        'children' => $this->formatSubscriberFields($subscriber_fields)
      )
    );
    if($subscriber_custom_fields) {
      array_push($select2Fields, array(
        'name' => __('User fields'),
        'children' => $this->formatSubscriberCustomFields(
          $subscriber_custom_fields
        )
      ));
    }
    return $select2Fields;
  }

  function bootstrap() {
    $subscriber_fields = $this->getSubscriberFields();
    $subscriber_custom_fields = $this->getSubscriberCustomFields();
    $data['segments'] = json_encode($this->getSegments());
    $data['subscriberFieldsSelect2'] = json_encode(
      $this->formatFieldsForSelect2(
        $subscriber_fields,
        $subscriber_custom_fields
      )
    );
    if($this->action === 'import') {
      $data['subscriberFields'] = json_encode(
        array_merge(
          $this->formatSubscriberFields($subscriber_fields),
          $this->formatSubscriberCustomFields($subscriber_custom_fields)
        )
      );
      $data['maxPostSizeBytes'] = Helpers::getMaxPostSize('bytes');
      $data['maxPostSize'] = Helpers::getMaxPostSize();
    } else {
      $data['segmentsWithConfirmedSubscribers'] =
        json_encode($this->getSegments($with_confirmed_subscribers = true));
    }
    return $data;
  }
}

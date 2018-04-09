<?php
namespace MailPoet\Subscribers\ImportExport;

use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Premium\Models\DynamicSegment;
use MailPoet\Util\Helpers;
use MailPoet\WP\Hooks;

class ImportExportFactory {
  const IMPORT_ACTION = 'import'; 
  const EXPORT_ACTION = 'export';

  public $action;

  function __construct($action = null) {
    $this->action = $action;
  }

  function getSegments() {
    if($this->action === self::IMPORT_ACTION) {
      $segments = Segment::getSegmentsForImport();
    } else {
      $segments = Segment::getSegmentsForExport();
      $segments = Hooks::applyFilters('mailpoet_segments_with_subscriber_count', $segments);
      $segments = array_values(array_filter($segments, function($segment) {
        return $segment['subscribers'] > 0;
      }));
    }

    return array_map(function($segment) {
      if(!$segment['name']) $segment['name'] = __('Not In List', 'mailpoet');
      if(!$segment['id']) $segment['id'] = 0;
      return array(
        'id' => $segment['id'],
        'name' => $segment['name'],
        'subscriberCount' => $segment['subscribers']
      );
    }, $segments);
  }

  function getSubscriberFields() {
    $fields = array(
      'email' => __('Email', 'mailpoet'),
      'first_name' => __('First name', 'mailpoet'),
      'last_name' => __('Last name', 'mailpoet')
    );
    if($this->action === 'export') {
      $fields = array_merge(
        $fields,
        array(
          'list_status' => _x('List status', 'Subscription status', 'mailpoet'),
          'global_status' => _x('Global status', 'Subscription status', 'mailpoet'),
          'subscribed_ip' => __('IP address', 'mailpoet')
        )
      );
    }
    return $fields;
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
        'params' => unserialize($field['params']),
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
          'name' => __('Ignore field...', 'mailpoet'),
        ),
        array(
          'id' => 'create',
          'name' => __('Create new field...', 'mailpoet')
        ),
      ) :
      array(
        array(
          'id' => 'select',
          'name' => __('Select all...', 'mailpoet'),
        ),
        array(
          'id' => 'deselect',
          'name' => __('Deselect all...', 'mailpoet')
        ),
      );
    $select2Fields = array(
      array(
        'name' => __('Actions', 'mailpoet'),
        'children' => $actions
      ),
      array(
        'name' => __('System fields', 'mailpoet'),
        'children' => $this->formatSubscriberFields($subscriber_fields)
      )
    );
    if($subscriber_custom_fields) {
      array_push($select2Fields, array(
        'name' => __('User fields', 'mailpoet'),
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
    }
    return $data;
  }
}

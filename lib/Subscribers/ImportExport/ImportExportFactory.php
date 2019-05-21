<?php
namespace MailPoet\Subscribers\ImportExport;

use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

class ImportExportFactory {
  const IMPORT_ACTION = 'import';
  const EXPORT_ACTION = 'export';

  public $action;

  private $wp;

  function __construct($action = null) {
    $this->action = $action;
    $this->wp = new WPFunctions;
  }

  function getSegments() {
    if ($this->action === self::IMPORT_ACTION) {
      $segments = Segment::getSegmentsForImport();
    } else {
      $segments = Segment::getSegmentsForExport();
      $segments = $this->wp->applyFilters('mailpoet_segments_with_subscriber_count', $segments);
      $segments = array_values(array_filter($segments, function($segment) {
        return $segment['subscribers'] > 0;
      }));
    }

    return array_map(function($segment) {
      if (!$segment['name']) $segment['name'] = WPFunctions::get()->__('Not In List', 'mailpoet');
      if (!$segment['id']) $segment['id'] = 0;
      return [
        'id' => $segment['id'],
        'name' => $segment['name'],
        'subscriberCount' => $segment['subscribers'],
      ];
    }, $segments);
  }

  function getSubscriberFields() {
    $fields = [
      'email' => WPFunctions::get()->__('Email', 'mailpoet'),
      'first_name' => WPFunctions::get()->__('First name', 'mailpoet'),
      'last_name' => WPFunctions::get()->__('Last name', 'mailpoet'),
    ];
    if ($this->action === 'export') {
      $fields = array_merge(
        $fields,
        [
          'list_status' => WPFunctions::get()->_x('List status', 'Subscription status', 'mailpoet'),
          'global_status' => WPFunctions::get()->_x('Global status', 'Subscription status', 'mailpoet'),
          'subscribed_ip' => WPFunctions::get()->__('IP address', 'mailpoet'),
        ]
      );
    }
    return $fields;
  }

  function formatSubscriberFields($subscriber_fields) {
    return array_map(function($field_id, $field_name) {
      return [
        'id' => $field_id,
        'name' => $field_name,
        'type' => ($field_id === 'confirmed_at') ? 'date' : null,
        'custom' => false,
      ];
    }, array_keys($subscriber_fields), $subscriber_fields);
  }

  function getSubscriberCustomFields() {
    return CustomField::findArray();
  }

  function formatSubscriberCustomFields($subscriber_custom_fields) {
    return array_map(function($field) {
      return [
        'id' => $field['id'],
        'name' => $field['name'],
        'type' => $field['type'],
        'params' => unserialize($field['params']),
        'custom' => true,
      ];
    }, $subscriber_custom_fields);
  }

  function formatFieldsForSelect2(
    $subscriber_fields,
    $subscriber_custom_fields) {
    $actions = ($this->action === 'import') ?
      [
        [
          'id' => 'ignore',
          'name' => WPFunctions::get()->__('Ignore field...', 'mailpoet'),
        ],
        [
          'id' => 'create',
          'name' => WPFunctions::get()->__('Create new field...', 'mailpoet'),
        ],
      ] :
      [
        [
          'id' => 'select',
          'name' => WPFunctions::get()->__('Select all...', 'mailpoet'),
        ],
        [
          'id' => 'deselect',
          'name' => WPFunctions::get()->__('Deselect all...', 'mailpoet'),
        ],
      ];
    $select2Fields = [
      [
        'name' => WPFunctions::get()->__('Actions', 'mailpoet'),
        'children' => $actions,
      ],
      [
        'name' => WPFunctions::get()->__('System fields', 'mailpoet'),
        'children' => $this->formatSubscriberFields($subscriber_fields),
      ],
    ];
    if ($subscriber_custom_fields) {
      array_push($select2Fields, [
        'name' => WPFunctions::get()->__('User fields', 'mailpoet'),
        'children' => $this->formatSubscriberCustomFields(
          $subscriber_custom_fields
        ),
      ]);
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
    if ($this->action === 'import') {
      $data['subscriberFields'] = json_encode(
        array_merge(
          $this->formatSubscriberFields($subscriber_fields),
          $this->formatSubscriberCustomFields($subscriber_custom_fields)
        )
      );
      $data['maxPostSizeBytes'] = Helpers::getMaxPostSize('bytes');
      $data['maxPostSize'] = Helpers::getMaxPostSize();
    }
    $data['zipExtensionLoaded'] = extension_loaded('zip');
    return $data;
  }
}

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

  public function __construct($action = null) {
    $this->action = $action;
    $this->wp = new WPFunctions;
  }

  public function getSegments() {
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
        'text' => $segment['name'], // Required select2 property
        'subscriberCount' => $segment['subscribers'],
      ];
    }, $segments);
  }

  public function getSubscriberFields() {
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

  public function formatSubscriberFields($subscriberFields) {
    return array_map(function($fieldId, $fieldName) {
      return [
        'id' => $fieldId,
        'name' => $fieldName,
        'type' => ($fieldId === 'confirmed_at') ? 'date' : null,
        'custom' => false,
      ];
    }, array_keys($subscriberFields), $subscriberFields);
  }

  public function getSubscriberCustomFields() {
    return CustomField::findArray();
  }

  public function formatSubscriberCustomFields($subscriberCustomFields) {
    return array_map(function($field) {
      return [
        'id' => $field['id'],
        'name' => $field['name'],
        'type' => $field['type'],
        'params' => unserialize($field['params']),
        'custom' => true,
      ];
    }, $subscriberCustomFields);
  }

  public function formatFieldsForSelect2(
    $subscriberFields,
    $subscriberCustomFields) {
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
        'children' => $this->formatSubscriberFields($subscriberFields),
      ],
    ];
    if ($subscriberCustomFields) {
      array_push($select2Fields, [
        'name' => WPFunctions::get()->__('User fields', 'mailpoet'),
        'children' => $this->formatSubscriberCustomFields(
          $subscriberCustomFields
        ),
      ]);
    }
    return $select2Fields;
  }

  public function bootstrap() {
    $subscriberFields = $this->getSubscriberFields();
    $subscriberCustomFields = $this->getSubscriberCustomFields();
    $data['segments'] = json_encode($this->getSegments());
    $data['subscriberFieldsSelect2'] = json_encode(
      $this->formatFieldsForSelect2(
        $subscriberFields,
        $subscriberCustomFields
      )
    );
    if ($this->action === 'import') {
      $data['subscriberFields'] = json_encode(
        array_merge(
          $this->formatSubscriberFields($subscriberFields),
          $this->formatSubscriberCustomFields($subscriberCustomFields)
        )
      );
      $data['maxPostSizeBytes'] = Helpers::getMaxPostSize('bytes');
      $data['maxPostSize'] = Helpers::getMaxPostSize();
    }
    $data['zipExtensionLoaded'] = extension_loaded('zip');
    return $data;
  }
}

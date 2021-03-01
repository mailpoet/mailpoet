<?php

namespace MailPoet\Subscribers\ImportExport;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Models\CustomField;
use MailPoet\Segments\SegmentsSimpleListRepository;
use MailPoet\Util\Helpers;

class ImportExportFactory {
  const IMPORT_ACTION = 'import';
  const EXPORT_ACTION = 'export';

  /** @var string|null  */
  public $action;

  /** @var SegmentsSimpleListRepository */
  private $segmentsListRepository;

  public function __construct($action = null) {
    $this->action = $action;
    $this->segmentsListRepository = ContainerWrapper::getInstance()->get(SegmentsSimpleListRepository::class);
  }

  public function getSegments() {
    if ($this->action === self::IMPORT_ACTION) {
      $segments = $this->segmentsListRepository->getListWithSubscribedSubscribersCounts([SegmentEntity::TYPE_DEFAULT, SegmentEntity::TYPE_WP_USERS]);
    } else {
      $segments = $this->segmentsListRepository->getListWithAssociatedSubscribersCounts();
      $segments = $this->segmentsListRepository->addVirtualSubscribersWithoutListSegment($segments);
      $segments = array_values(array_filter($segments, function($segment) {
        return $segment['subscribers'] > 0;
      }));
    }

    return array_map(function($segment) {
      return [
        'id' => $segment['id'],
        'name' => $segment['name'],
        'count' => $segment['subscribers'],
      ];
    }, $segments);
  }

  public function getSubscriberFields() {
    $fields = [
      'email' => __('Email', 'mailpoet'),
      'first_name' => __('First name', 'mailpoet'),
      'last_name' => __('Last name', 'mailpoet'),
    ];
    if ($this->action === 'export') {
      $fields = array_merge(
        $fields,
        [
          'list_status' => _x('List status', 'Subscription status', 'mailpoet'),
          'global_status' => _x('Global status', 'Subscription status', 'mailpoet'),
          'subscribed_ip' => __('Subscription IP', 'mailpoet'),
          'created_at' => __('Subscription time', 'mailpoet'),
          'confirmed_at' => __('Confirmation time', 'mailpoet'),
          'confirmed_ip' => __('Confirmation IP', 'mailpoet'),
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
        'type' => ($fieldId === 'confirmed_at' || $fieldId === 'created_at') ? 'date' : null,
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
          'name' => __('Ignore field...', 'mailpoet'),
        ],
        [
          'id' => 'create',
          'name' => __('Create new field...', 'mailpoet'),
        ],
      ] :
      [
        [
          'id' => 'select',
          'name' => __('Select all...', 'mailpoet'),
        ],
        [
          'id' => 'deselect',
          'name' => __('Deselect all...', 'mailpoet'),
        ],
      ];
    $select2Fields = [
      [
        'name' => __('Actions', 'mailpoet'),
        'children' => $actions,
      ],
      [
        'name' => __('System fields', 'mailpoet'),
        'children' => $this->formatSubscriberFields($subscriberFields),
      ],
    ];
    if ($subscriberCustomFields) {
      array_push($select2Fields, [
        'name' => __('User fields', 'mailpoet'),
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

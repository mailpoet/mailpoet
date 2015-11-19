<?php
namespace MailPoet\Subscribers\ImportExport;

use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Util\Helpers;

class BootStrapMenu {
  function __construct($action = null) {
    $this->action = $action;
  }

  function getSegments($withConfirmedSubscribers = false) {
    $segments = ($this->action === 'import') ?
      Segment::getSegmentsForImport() :
      Segment::getSegmentsForExport($withConfirmedSubscribers);
    return array_map(function ($segment) {
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
      /*
            'confirmed_ip' => __('IP address')
            'confirmed_at' => __('Subscription date')
      */
    );
  }

  function formatSubscriberFields($subscriberFields) {
    return array_map(function ($fieldId, $fieldName) {
      return array(
        'id' => $fieldId,
        'name' => $fieldName,
        'type' => ($fieldId === 'confirmed_at') ? 'date' : null,
        'custom' => false
      );
    }, array_keys($subscriberFields), $subscriberFields);
  }

  function getSubscriberCustomFields() {
    return CustomField::findArray();
  }

  function formatSubscriberCustomFields($subscriberCustomFields) {
    return array_map(function ($field) {
      return array(
        'id' => $field['id'],
        'name' => $field['name'],
        'type' => $field['type'],
        'custom' => true
      );
    }, $subscriberCustomFields);
  }

  function formatFieldsForSelect2(
    $subscriberFields,
    $subscriberCustomFields) {
    $actions = ($this->action === 'import') ?
      array(
        array(
          'id' => 'ignore',
          'name' => __('Ignore column...'),
        ),
        array(
          'id' => 'create',
          'name' => __('Create new column...')
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
        'name' => __('System columns'),
        'children' => $this->formatSubscriberFields($subscriberFields)
      )
    );
    if($subscriberCustomFields) {
      array_push($select2Fields, array(
        'name' => __('User columns'),
        'children' => $this->formatSubscriberCustomFields(
          $subscriberCustomFields
        )
      ));
    }
    return $select2Fields;
  }

  function bootstrap() {
    $subscriberFields = $this->getSubscriberFields();
    $subscriberCustomFields = $this->getSubscriberCustomFields();
    $data['segments'] = json_encode($this->getSegments());
    $data['subscriberFieldsSelect2'] = json_encode(
      $this->formatFieldsForSelect2(
        $subscriberFields,
        $subscriberCustomFields
      )
    );
    if($this->action === 'import') {
      $data['subscriberFields'] = json_encode(
        array_merge(
          $this->formatSubscriberFields($subscriberFields),
          $this->formatSubscriberCustomFields($subscriberCustomFields)
        )
      );
      $data['maxPostSizeBytes'] = Helpers::getMaxPostSize('bytes');
      $data['maxPostSize'] = Helpers::getMaxPostSize();
    } else {
      $data['segmentsWithConfirmedSubscribers'] =
        json_encode($this->getSegments($withConfirmedSubscribers = true));
    }
    return $data;
  }
}

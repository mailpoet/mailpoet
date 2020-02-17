<?php

namespace MailPoet\Subscribers\ImportExport\PersonalDataExporters;

use MailPoet\Models\CustomField;
use MailPoet\Models\Subscriber;
use MailPoet\Subscribers\Source;
use MailPoet\WP\Functions as WPFunctions;

class SubscriberExporter {
  public function export($email) {
    return [
      'data' => $this->exportSubscriber(Subscriber::findOne(trim($email))),
      'done' => true,
    ];
  }

  private function exportSubscriber($subscriber) {
    if (!$subscriber) return [];
    return [[
      'group_id' => 'mailpoet-subscriber',
      'group_label' => WPFunctions::get()->__('MailPoet Subscriber Data', 'mailpoet'),
      'item_id' => 'subscriber-' . $subscriber->id,
      'data' => $this->getSubscriberExportData($subscriber->withCustomFields()),
    ]];
  }

  private function getSubscriberExportData($subscriber) {
    $customFields = $this->getCustomFields();
    $result = [
      [
        'name' => WPFunctions::get()->__('First Name', 'mailpoet'),
        'value' => $subscriber->firstName,
      ],
      [
        'name' => WPFunctions::get()->__('Last Name', 'mailpoet'),
        'value' => $subscriber->lastName,
      ],
      [
        'name' => WPFunctions::get()->__('Email', 'mailpoet'),
        'value' => $subscriber->email,
      ],
      [
        'name' => WPFunctions::get()->__('Status', 'mailpoet'),
        'value' => $subscriber->status,
      ],
    ];
    if ($subscriber->subscribedIp) {
      $result[] = [
        'name' => WPFunctions::get()->__('Subscribed IP', 'mailpoet'),
        'value' => $subscriber->subscribedIp,
      ];
    }
    if ($subscriber->confirmedIp) {
      $result[] = [
        'name' => WPFunctions::get()->__('Confirmed IP', 'mailpoet'),
        'value' => $subscriber->confirmedIp,
      ];
    }
    $result[] = [
      'name' => WPFunctions::get()->__('Created at', 'mailpoet'),
      'value' => $subscriber->createdAt,
    ];

    foreach ($customFields as $customFieldId => $customFieldName) {
      $customFieldValue = $subscriber->{$customFieldId};
      if ($customFieldValue) {
        $result[] = [
          'name' => $customFieldName,
          'value' => $customFieldValue,
        ];
      }
    }

    $result[] = [
      'name' => WPFunctions::get()->__("Subscriber's subscription source", 'mailpoet'),
      'value' => $this->formatSource($subscriber->source),
    ];

    return $result;
  }

  private function getCustomFields() {
    $fields = CustomField::findMany();
    $result = [];
    foreach ($fields as $field) {
      $result['cf_' . $field->id] = $field->name;
    }
    return $result;
  }

  private function formatSource($source) {
    switch ($source) {
      case Source::WORDPRESS_USER:
        return WPFunctions::get()->__('Subscriber information synchronized via WP user sync', 'mailpoet');
      case Source::FORM:
        return WPFunctions::get()->__('Subscription via a MailPoet subscription form', 'mailpoet');
      case Source::API:
        return WPFunctions::get()->__('Added by a 3rd party via MailPoet 3 API', 'mailpoet');
      case Source::ADMINISTRATOR:
        return WPFunctions::get()->__('Created by the administrator', 'mailpoet');
      case Source::IMPORTED:
        return WPFunctions::get()->__('Imported by the administrator', 'mailpoet');
      default:
        return WPFunctions::get()->__('Unknown', 'mailpoet');
    }
  }
}

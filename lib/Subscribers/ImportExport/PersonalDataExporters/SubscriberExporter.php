<?php

namespace MailPoet\Subscribers\ImportExport\PersonalDataExporters;

use MailPoet\Models\CustomField;
use MailPoet\Models\Subscriber;
use MailPoet\Subscribers\Source;

class SubscriberExporter {

  function export($email) {
    return array(
      'data' => $this->exportSubscriber(Subscriber::findOne(trim($email))),
      'done' => true,
    );
  }

  private function exportSubscriber($subscriber) {
    if(!$subscriber) return array();
    return array(array(
      'group_id' => 'mailpoet-subscriber',
      'group_label' => __('MailPoet Subscriber Data', 'mailpoet'),
      'item_id' => 'subscriber-' . $subscriber->id,
      'data' => $this->getSubscriberExportData($subscriber->withCustomFields()),
    ));
  }

  private function getSubscriberExportData($subscriber) {
    $custom_fields = $this->getCustomFields();
    $result = array(
      array(
        'name' => __('First Name', 'mailpoet'),
        'value' => $subscriber->first_name,
      ),
      array(
        'name' => __('Last Name', 'mailpoet'),
        'value' => $subscriber->last_name,
      ),
      array(
        'name' => __('Email', 'mailpoet'),
        'value' => $subscriber->email,
      ),
      array(
        'name' => __('Status', 'mailpoet'),
        'value' => $subscriber->status,
      ),
    );
    if($subscriber->subscribed_ip) {
      $result[] = array(
        'name' => __('Subscribed IP', 'mailpoet'),
        'value' => $subscriber->subscribed_ip,
      );
    }
    if($subscriber->confirmed_ip) {
      $result[] = array(
        'name' => __('Confirmed IP', 'mailpoet'),
        'value' => $subscriber->confirmed_ip,
      );
    }
    $result[] = array(
      'name' => __('Created at', 'mailpoet'),
      'value' => $subscriber->created_at,
    );

    foreach($custom_fields as $custom_field_id => $custom_field_name) {
      $custom_field_value = $subscriber->{$custom_field_id};
      if($custom_field_value) {
        $result[] = array(
          'name' => $custom_field_name,
          'value' => $custom_field_value,
        );
      }
    }

    $result[] = array(
      'name' => __("Subscriber's subscription source", 'mailpoet'),
      'value' => $this->formatSource($subscriber->source),
    );

    return $result;
  }

  private function getCustomFields() {
    $fields = CustomField::findMany();
    $result = array();
    foreach($fields as $field) {
      $result['cf_' . $field->id] = $field->name;
    }
    return $result;
  }

  private function formatSource($source) {
    switch ($source) {
      case Source::WORDPRESS_USER:
        return __('Subscriber information synchronized via WP user sync', 'mailpoet');
      case Source::FORM:
        return __('Subscription via a MailPoet subscription form', 'mailpoet');
      case Source::API:
        return __('Added by a 3rd party via MailPoet 3 API', 'mailpoet');
      case Source::ADMINISTRATOR:
        return __('Created by the administrator', 'mailpoet');
      case Source::IMPORTED:
        return __('Imported by the administrator', 'mailpoet');
      default:
        return __('Unknown', 'mailpoet');
    }
  }

}
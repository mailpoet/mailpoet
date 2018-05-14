<?php

namespace MailPoet\Subscribers\ImportExport\PersonalDataExporters;

use MailPoet\Models\CustomField;
use MailPoet\Models\Subscriber;

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

}
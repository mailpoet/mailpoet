<?php
namespace MailPoet\Subscribers\ImportExport\PersonalDataExporters;

use MailPoet\Models\Subscriber;
use MailPoet\WP\Functions as WPFunctions;

class SegmentsExporter {

  function export($email) {
    return [
      'data' => $this->exportSubscriber(Subscriber::findOne(trim($email))),
      'done' => true,
    ];
  }

  private function exportSubscriber($subscriber) {
    if (!$subscriber) return [];

    $result = [];
    $segments = $subscriber->getAllSegmentNamesWithStatus();

    foreach ($segments as $segment) {
      $result[] = $this->exportSegment($segment);
    }

    return $result;
  }

  private function exportSegment($segment) {
    $segment_data = [];
    $segment_data[] = [
      'name' => WPFunctions::get()->__('List name', 'mailpoet'),
      'value' => $segment['name'],
    ];
    $segment_data[] = [
      'name' => WPFunctions::get()->__('Subscription status', 'mailpoet'),
      'value' => $segment['status'],
    ];
    $segment_data[] = [
      'name' => WPFunctions::get()->__('Timestamp of the subscription (or last change of the subscription status)', 'mailpoet'),
      'value' => $segment['updated_at'],
    ];
    return [
      'group_id' => 'mailpoet-lists',
      'group_label' => WPFunctions::get()->__('MailPoet Mailing Lists', 'mailpoet'),
      'item_id' => 'list-' . $segment['segment_id'],
      'data' => $segment_data,
    ];
  }


}

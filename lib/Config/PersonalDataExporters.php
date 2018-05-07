<?php

namespace MailPoet\Config;

use MailPoet\Subscribers\ImportExport\PersonalDataExporters\NewslettersExporter;
use MailPoet\Subscribers\ImportExport\PersonalDataExporters\SegmentsExporter;
use MailPoet\Subscribers\ImportExport\PersonalDataExporters\SubscriberExporter;

class PersonalDataExporters {

  function init() {
    add_filter('wp_privacy_personal_data_exporters', array($this, 'registerSubscriberExporter'));
    add_filter('wp_privacy_personal_data_exporters', array($this, 'registerSegmentsExporter'));
    add_filter('wp_privacy_personal_data_exporters', array($this, 'registerNewslettersExporter'));
  }

  function registerSegmentsExporter($exporters) {
    $exporters[] = array(
      'exporter_friendly_name' => __('MailPoet Lists'),
      'callback' => array(new SegmentsExporter(), 'export'),
    );
    return $exporters;
  }

  function registerSubscriberExporter($exporters) {
    $exporters[] = array(
      'exporter_friendly_name' => __('MailPoet Subscriber Data'),
      'callback' => array(new SubscriberExporter(), 'export'),
    );
    return $exporters;
  }

  function registerNewslettersExporter($exporters) {
    $exporters[] = array(
      'exporter_friendly_name' => __('MailPoet Emails'),
      'callback' => array(new NewslettersExporter(), 'export'),
    );
    return $exporters;
  }

}
